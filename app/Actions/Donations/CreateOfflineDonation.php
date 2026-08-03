<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\PaymentMode;
use App\Models\Donation;
use App\Models\Donor;
use App\Services\Settings\SettingsRepository;
use App\Support\FinancialYear;
use App\Support\Money;

/**
 * Cash, cheque, or bank transfer recorded by an admin — no gateway, no
 * `payment_transactions` row. See docs/modules/M05-donations-payments.md's
 * "Offline donations" section.
 *
 * Cash above the configured limit (`donation.cash_80g_limit`, ₹2,000 by
 * default per Indian tax law) is automatically ineligible for 80G — this is
 * not a silent decision; the admin UI is expected to explain why.
 */
final class CreateOfflineDonation
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    public function handle(
        string $donorName,
        string $donorEmail,
        ?string $donorPhone,
        Money $amount,
        PaymentMode $paymentMode,
        int $recordedByUserId,
        ?int $campaignId = null,
        ?string $donatedOn = null,
        ?string $notes = null,
    ): Donation {
        $donor = Donor::findByEmail($donorEmail) ?? Donor::query()->create([
            'name' => $donorName,
            'email' => trim(mb_strtolower($donorEmail)),
            'phone' => $donorPhone,
        ]);

        $donatedAt = $donatedOn ?? now()->toDateTimeString();
        $financialYear = FinancialYear::for($donatedAt)->toString();

        $donation = Donation::query()->create([
            'donor_id' => $donor->id,
            'campaign_id' => $campaignId,
            'amount' => $amount->toPaise(),
            'free_amount' => $amount->toPaise(),
            'currency' => 'INR',
            'type' => 'one_time',
            'payment_mode' => $paymentMode->value,
            'status' => 'succeeded',
            'is_offline' => true,
            'donated_at' => $donatedAt,
            'financial_year' => $financialYear,
            'eligible_for_80g' => $this->isEligibleFor80g($paymentMode, $amount),
            'source' => 'admin',
            'recorded_by_user_id' => $recordedByUserId,
            'notes' => $notes,
        ]);

        $donation->update(['donation_number' => sprintf('DN-%s-%06d', $financialYear, $donation->id)]);

        $donor->update([
            'total_donated' => $donor->total_donated + $amount->toPaise(),
            'donation_count' => $donor->donation_count + 1,
            'first_donated_at' => $donor->first_donated_at ?? now(),
            'last_donated_at' => now(),
        ]);

        return $donation->fresh();
    }

    private function isEligibleFor80g(PaymentMode $mode, Money $amount): bool
    {
        if ($mode !== PaymentMode::Cash) {
            return true;
        }

        $limit = (int) $this->settings->get('donation.cash_80g_limit', 200000);

        return $amount->toPaise() <= $limit;
    }
}
