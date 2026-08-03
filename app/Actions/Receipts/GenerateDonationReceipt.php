<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Enums\ReceiptSeries;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Receipt;

/**
 * The plain acknowledgement receipt every successful donation gets — no
 * statutory requirements, issued instantly, always. See
 * docs/modules/M07-receipts-80g.md's "Why two series" section. For the
 * tax-deduction certificate see Generate80GReceipt, which has real
 * eligibility gates this one deliberately doesn't.
 */
final class GenerateDonationReceipt
{
    public function __construct(
        private readonly IssueReceipt $issueReceipt,
    ) {}

    public function handle(Donation $donation): Receipt
    {
        $donor = $donation->donor;

        return $this->issueReceipt->handle($donation, ReceiptSeries::Donation, $this->snapshot($donation, $donor));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Donation $donation, Donor $donor): array
    {
        return [
            'donor_name' => $donor->is_anonymous ? 'Anonymous donor' : $donor->name,
            'donor_email' => $donor->email,
            'donor_phone' => $donor->phone,
            'donor_pan' => $donor->pan,
            'donor_address' => trim(implode(', ', array_filter([
                $donor->address_line1, $donor->address_line2, $donor->city, $donor->state, $donor->pincode,
            ]))),
            'donation_number' => $donation->donation_number,
            'payment_mode' => $donation->payment_mode?->label(),
            'donated_at' => $donation->donated_at?->toDateTimeString(),
            'message' => $donation->message,
            'is_anonymous' => $donor->is_anonymous,
        ];
    }
}
