<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Enums\ReceiptSeries;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Receipt;
use App\Services\Settings\SettingsRepository;
use App\Support\FinancialYear;
use InvalidArgumentException;

/**
 * The tax-deduction certificate — strict content requirements, strict
 * numbering, only issued when the donation and donor qualify. See
 * docs/modules/M07-receipts-80g.md's "Eligibility rules" table; every gate
 * below corresponds exactly to a row in it, in the same order, so a support
 * request ("why didn't my donor get an 80G receipt?") can be answered by
 * reading this method top to bottom.
 *
 * `snapshot_data` freezes every field the receipt shows — NGO identity, 80G
 * registration, donor identity — at issue time. If the NGO's registered
 * address changes in 2028, a 2026 receipt must still show the 2026 address;
 * that's what the donor filed with their return.
 */
final class Generate80GReceipt
{
    public function __construct(
        private readonly IssueReceipt $issueReceipt,
        private readonly SettingsRepository $settings,
    ) {}

    public function handle(Donation $donation): Receipt
    {
        $this->assertEligible($donation);

        $donor = $donation->donor;

        return $this->issueReceipt->handle($donation, ReceiptSeries::EightyG, $this->snapshot($donation, $donor));
    }

    /**
     * Returns the reason an 80G receipt cannot (yet) be issued, or null if
     * it can. Used by the UI to explain rather than fail opaquely — see
     * docs/modules/M07-receipts-80g.md: "Each reason is shown clearly."
     */
    public function ineligibilityReason(Donation $donation): ?string
    {
        try {
            $this->assertEligible($donation);

            return null;
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }

    private function assertEligible(Donation $donation): void
    {
        if ($donation->status->value !== 'succeeded') {
            throw new InvalidArgumentException('Donation not completed.');
        }

        if (! $donation->eligible_for_80g) {
            throw new InvalidArgumentException(
                'This donation is not eligible for 80G (for example, cash over ₹2,000).'
            );
        }

        $donor = $donation->donor;

        if (! $donor->pan) {
            throw new InvalidArgumentException('Donor PAN is required for an 80G receipt.');
        }

        if (! $donor->address_line1 || ! $donor->city || ! $donor->state || ! $donor->pincode) {
            throw new InvalidArgumentException('Donor address is required for an 80G receipt.');
        }

        $validTo = $this->settings->get('org.80g_valid_to');

        if ($validTo && now()->toDateString() > $validTo) {
            throw new InvalidArgumentException("The NGO's 80G registration has expired.");
        }

        $donatedAt = $donation->donated_at?->toDateString();
        $validFrom = $this->settings->get('org.80g_valid_from');

        if ($donatedAt && (($validFrom && $donatedAt < $validFrom) || ($validTo && $donatedAt > $validTo))) {
            throw new InvalidArgumentException(
                "This donation's date falls outside the NGO's 80G registration validity period."
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Donation $donation, Donor $donor): array
    {
        return [
            'org_name' => $this->settings->get('org.name'),
            'org_address' => trim(implode(', ', array_filter([
                $this->settings->get('org.address_line1'),
                $this->settings->get('org.address_line2'),
                $this->settings->get('org.city'),
                $this->settings->get('org.state'),
                $this->settings->get('org.pincode'),
            ]))),
            'org_pan' => $this->settings->get('org.pan'),
            'org_80g_number' => $this->settings->get('org.80g_number'),
            'org_80g_valid_from' => $this->settings->get('org.80g_valid_from'),
            'org_80g_valid_to' => $this->settings->get('org.80g_valid_to'),
            'org_12a_number' => $this->settings->get('org.12a_number'),
            'signatory_name' => $this->settings->get('org.authorised_signatory_name'),
            'signatory_designation' => $this->settings->get('org.authorised_signatory_designation'),
            'deduction_statement' => $this->settings->get('receipt.80g_deduction_statement'),
            'purpose' => $donation->campaign_id ? "Campaign #{$donation->campaign_id}" : 'General Fund',
            'donor_name' => $donor->is_anonymous ? 'Anonymous donor' : $donor->name,
            'donor_pan' => $donor->pan,
            'donor_address' => trim(implode(', ', array_filter([
                $donor->address_line1, $donor->address_line2, $donor->city, $donor->state, $donor->pincode,
            ]))),
            'donation_number' => $donation->donation_number,
            'payment_mode' => $donation->payment_mode?->label(),
            'donated_at' => $donation->donated_at?->toDateTimeString(),
            'financial_year' => FinancialYear::for($donation->donated_at ?? now())->toString(),
        ];
    }
}
