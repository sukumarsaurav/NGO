<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Events\DonationSucceeded;
use App\Mail\SubscriptionChargeFailedMail;
use App\Mail\SubscriptionHaltedMail;
use App\Models\Donation;
use App\Models\Subscription;
use App\Models\SubscriptionCharge;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Mail;

/**
 * webhook: subscription.charged (success) / subscription.pending (failure,
 * will retry) — see docs/modules/M06-recurring-autopay.md steps 7 and
 * "Failure handling". `UNIQUE(subscription_id, cycle_number)` is what makes
 * a duplicate `subscription.charged` webhook safe — see the edge case list.
 *
 * A cycle is one row across its whole lifetime, not one row per attempt —
 * `retry_count` is what tracks repeated attempts for the *same* cycle
 * (Razorpay doesn't advance to the next cycle number until a charge
 * succeeds). A failed attempt followed by a successful retry updates that
 * same row from `failed` to `succeeded` rather than colliding with it on
 * the unique constraint.
 *
 * A successful charge creates a linked `donations` row (`type = recurring`)
 * so every downstream concern — 80G receipts, campaign totals, reporting —
 * works identically for one-time and recurring gifts with no special-casing.
 */
final class RecordSubscriptionCharge
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function recordSuccess(string $providerSubscriptionId, string $providerPaymentId, int $amountPaise): SubscriptionCharge
    {
        return $this->db->transaction(function () use ($providerSubscriptionId, $providerPaymentId, $amountPaise) {
            $subscription = Subscription::query()
                ->where('provider_subscription_id', $providerSubscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            $byPaymentId = SubscriptionCharge::query()
                ->where('subscription_id', $subscription->id)
                ->where('provider_payment_id', $providerPaymentId)
                ->first();

            if ($byPaymentId) {
                // The exact same webhook, replayed.
                return $byPaymentId;
            }

            $cycleNumber = $subscription->completed_cycles + 1;

            $existingCycleRow = SubscriptionCharge::query()
                ->where('subscription_id', $subscription->id)
                ->where('cycle_number', $cycleNumber)
                ->first();

            if ($existingCycleRow?->status->value === 'succeeded') {
                // This cycle already succeeded via a different payment
                // attempt — never double-record it.
                return $existingCycleRow;
            }

            if ($existingCycleRow) {
                // A prior attempt for this same cycle failed; this webhook
                // is the successful retry — transition that row, don't
                // create a second one (would violate the unique constraint).
                $existingCycleRow->update([
                    'amount' => $amountPaise,
                    'status' => 'succeeded',
                    'provider_payment_id' => $providerPaymentId,
                    'charged_at' => now(),
                    'retry_count' => $existingCycleRow->retry_count + 1,
                ]);
                $charge = $existingCycleRow->fresh();
            } else {
                $charge = SubscriptionCharge::query()->create([
                    'subscription_id' => $subscription->id,
                    'cycle_number' => $cycleNumber,
                    'amount' => $amountPaise,
                    'status' => 'succeeded',
                    'provider_payment_id' => $providerPaymentId,
                    'scheduled_for' => $subscription->next_charge_at ?? now(),
                    'charged_at' => now(),
                ]);
            }

            $donation = Donation::query()->create([
                'donor_id' => $subscription->donor_id,
                'campaign_id' => $subscription->campaign_id,
                'subscription_id' => $subscription->id,
                'amount' => $amountPaise,
                'free_amount' => $amountPaise,
                'currency' => 'INR',
                'type' => 'recurring',
                'payment_mode' => 'upi',
                'status' => 'succeeded',
                'is_offline' => false,
                'donated_at' => now(),
                'financial_year' => FinancialYear::current()->toString(),
                'eligible_for_80g' => true,
                'source' => 'subscription',
            ]);
            $donation->update(['donation_number' => sprintf('DN-%s-%06d', $donation->financial_year, $donation->id)]);

            $charge->update(['donation_id' => $donation->id]);

            $totalCyclesReached = $subscription->total_cycles !== null && $cycleNumber >= $subscription->total_cycles;

            $subscription->update([
                'completed_cycles' => $cycleNumber,
                'failed_charge_count' => 0,
                'last_charged_at' => now(),
                'total_collected' => $subscription->total_collected + $amountPaise,
                'next_charge_at' => $totalCyclesReached ? null : $subscription->interval->addTo(now()),
                'status' => $totalCyclesReached ? SubscriptionStatus::Completed->value : $subscription->status->value,
            ]);

            DonationSucceeded::dispatch($donation->fresh());

            return $charge->fresh();
        });
    }

    /**
     * Three consecutive failures halt the subscription — see the "Failure
     * handling" flow in docs/modules/M06-recurring-autopay.md. Tone in the
     * emails matters: these are donors, not delinquent customers.
     */
    public function recordFailure(string $providerSubscriptionId, ?string $reason): SubscriptionCharge
    {
        return $this->db->transaction(function () use ($providerSubscriptionId, $reason) {
            $subscription = Subscription::query()
                ->where('provider_subscription_id', $providerSubscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            $cycleNumber = $subscription->completed_cycles + 1;

            $charge = SubscriptionCharge::query()
                ->where('subscription_id', $subscription->id)
                ->where('cycle_number', $cycleNumber)
                ->first();

            if ($charge) {
                $charge->update([
                    'status' => 'failed',
                    'failure_reason' => $reason,
                    'retry_count' => $charge->retry_count + 1,
                ]);
            } else {
                $charge = SubscriptionCharge::query()->create([
                    'subscription_id' => $subscription->id,
                    'cycle_number' => $cycleNumber,
                    'amount' => $subscription->amount,
                    'status' => 'failed',
                    'scheduled_for' => $subscription->next_charge_at ?? now(),
                    'failure_reason' => $reason,
                ]);
            }

            $failedCount = $subscription->failed_charge_count + 1;
            $shouldHalt = $failedCount >= 3;

            $subscription->update([
                'failed_charge_count' => $failedCount,
                'status' => $shouldHalt ? SubscriptionStatus::Halted->value : $subscription->status->value,
            ]);

            $subscription = $subscription->fresh();

            Mail::to($subscription->donor->email)->queue(new SubscriptionChargeFailedMail($subscription, $reason));

            if ($shouldHalt) {
                Mail::to($subscription->donor->email)->queue(new SubscriptionHaltedMail($subscription));
                // Admin notification: queued to every super-admin/admin user.
                foreach (User::role(['super-admin', 'admin'])->get() as $admin) {
                    Mail::to($admin->email)->queue(new SubscriptionHaltedMail($subscription));
                }
            }

            return $charge->fresh();
        });
    }
}
