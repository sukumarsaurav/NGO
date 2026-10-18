<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Events\DonationSucceeded;
use App\Models\Donation;
use App\Support\FinancialYear;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * An admin has seen the money arrive in the bank account and confirms the donation.
 *
 * This is the only point at which a direct-UPI donation becomes real: it counts toward the
 * campaign, enters the donor's history and triggers the thank-you / receipt flow, exactly as
 * a gateway-captured donation does in RecordSuccessfulDonation.
 *
 * Row-locked and idempotent: two admins clicking Confirm, or a double-click, must not add the
 * amount to the donor's total twice.
 */
final class ConfirmUpiDonation
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(Donation $donation, int $confirmedByUserId): Donation
    {
        return $this->db->transaction(function () use ($donation, $confirmedByUserId) {
            $donation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

            if ($donation->status === DonationStatus::Succeeded) {
                return $donation;
            }

            if ($donation->status !== DonationStatus::Pending
                || $donation->payment_mode !== PaymentMode::Upi
                || ! $donation->is_offline) {
                throw new InvalidArgumentException('Only a pending direct-UPI donation can be confirmed this way.');
            }

            $donatedAt = now();

            $donation->update([
                'status' => DonationStatus::Succeeded->value,
                'donated_at' => $donatedAt,
                'financial_year' => FinancialYear::for($donatedAt->toDateTimeString())->toString(),
                'recorded_by_user_id' => $confirmedByUserId,
            ]);

            $donor = $donation->donor;
            $donor->update([
                'total_donated' => $donor->total_donated + $donation->amount,
                'donation_count' => $donor->donation_count + 1,
                'first_donated_at' => $donor->first_donated_at ?? $donatedAt,
                'last_donated_at' => $donatedAt,
            ]);

            $donation = $donation->fresh();

            DonationSucceeded::dispatch($donation);

            return $donation;
        });
    }
}
