<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Models\Donation;
use InvalidArgumentException;

/**
 * No matching credit was found in the bank statement — the donor never paid, paid a
 * different amount, or reported a wrong reference. Cancels the pending claim.
 *
 * Releases the reference (`upi_reference` is UNIQUE), so a donor who mistyped it can be
 * helped to submit the right one against a new donation instead of being locked out.
 */
final class RejectUpiDonation
{
    public function handle(Donation $donation, ?string $reason = null): Donation
    {
        if ($donation->status !== DonationStatus::Pending
            || $donation->payment_mode !== PaymentMode::Upi
            || ! $donation->is_offline) {
            throw new InvalidArgumentException('Only a pending direct-UPI donation can be rejected this way.');
        }

        $note = 'UPI payment not verified'
            .($donation->upi_reference ? " (reported reference {$donation->upi_reference})" : '')
            .($reason ? ": {$reason}" : '.');

        $donation->update([
            'status' => DonationStatus::Cancelled->value,
            'upi_reference' => null,
            'notes' => trim(($donation->notes ? $donation->notes."\n" : '').$note),
        ]);

        return $donation->fresh();
    }
}
