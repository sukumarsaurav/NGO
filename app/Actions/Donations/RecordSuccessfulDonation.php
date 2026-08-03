<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Events\DonationSucceeded;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use App\Services\Payment\DTOs\PaymentResult;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Idempotency layer 2 — row locking. See docs/modules/M05-donations-payments.md:
 * either the client callback or the webhook may arrive first, both may
 * arrive, order is not guaranteed. Whichever gets here second finds the
 * donation already `succeeded` and no-ops.
 *
 * Layer 1 (webhook_events UNIQUE(provider, event_id)) lives in
 * RazorpayWebhookController, one level up — this action is called at most
 * once per genuinely-new event, but is still safe to call twice because a
 * client callback can race a webhook for the *same* payment.
 */
final class RecordSuccessfulDonation
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(string $orderId, PaymentResult $result): Donation
    {
        return $this->db->transaction(function () use ($orderId, $result) {
            $transaction = PaymentTransaction::query()
                ->where('provider_order_id', $orderId)
                ->first();

            if (! $transaction) {
                // Webhook for an unknown order_id — log and let the caller
                // decide how to respond (still 200, per the module doc's
                // "never 500 on an unrecognised webhook" rule).
                throw new RuntimeException("No payment_transactions row for order '{$orderId}'.");
            }

            $donation = Donation::query()->whereKey($transaction->donation_id)->lockForUpdate()->firstOrFail();

            if ($donation->status === DonationStatus::Succeeded) {
                return $donation;
            }

            $donation->update([
                'status' => DonationStatus::Succeeded->value,
                'donated_at' => now(),
                'payment_mode' => $this->mapPaymentMode($result->method),
                'donation_number' => $donation->donation_number ?? $this->donationNumber($donation),
            ]);

            $transaction->update([
                'provider_payment_id' => $result->paymentId,
                'status' => 'captured',
                'fee' => $result->fee->toPaise(),
                'tax' => $result->tax->toPaise(),
                'net_amount' => $result->netAmount()->toPaise(),
                'method' => $result->method,
                'bank' => $result->bank,
                'vpa' => $result->vpa,
                'card_last4' => $result->cardLast4,
                'captured_at' => $result->capturedAt ?? now(),
                'raw_response' => $result->raw,
            ]);

            $donor = $donation->donor;
            $donor->update([
                'total_donated' => $donor->total_donated + $donation->amount,
                'donation_count' => $donor->donation_count + 1,
                'first_donated_at' => $donor->first_donated_at ?? now(),
                'last_donated_at' => now(),
            ]);

            $donation = $donation->fresh();

            DonationSucceeded::dispatch($donation);

            return $donation;
        });
    }

    private function mapPaymentMode(?string $method): ?string
    {
        return match ($method) {
            'upi' => PaymentMode::Upi->value,
            'card' => PaymentMode::Card->value,
            'netbanking' => PaymentMode::Netbanking->value,
            'wallet' => PaymentMode::Wallet->value,
            null => null,
            default => PaymentMode::Other->value,
        };
    }

    /**
     * A simple, collision-free number derived from the donation's own
     * primary key — not a row-locked shared sequence, because nothing in
     * this sprint requires the sequential-per-year guarantee that
     * ReceiptNumberGenerator (M07) does for the legal receipt number.
     */
    private function donationNumber(Donation $donation): string
    {
        return sprintf('DN-%s-%06d', $donation->financial_year, $donation->id);
    }
}
