<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\PaymentMode;
use App\Models\Donation;
use App\Support\Money;
use App\Support\PaymentMethods;
use InvalidArgumentException;

/**
 * Starts a direct-UPI donation: the donor pays from their own UPI app straight to the
 * organisation's bank account, with no payment gateway in between.
 *
 * What this creates is a *pending claim*, not a donation that has happened. Nothing here can
 * know whether money arrived — the donor's app talks to their bank, not to us. The donation
 * stays `pending` (no receipt, not counted in campaign totals, not in the donor's history)
 * until an admin matches the donor's reported UPI reference against the bank statement and
 * runs ConfirmUpiDonation. Only that step fires DonationSucceeded.
 *
 * Marked `is_offline`: like cash or a cheque it is money that reached the account without
 * passing through a gateway, so there is no `payment_transactions` row and no gateway refund
 * is possible — a refund has to be made by bank transfer.
 */
final class InitiateUpiDonation
{
    public function __construct(
        private readonly CreatePendingDonation $createPending,
    ) {}

    /**
     * @param  array<string, string>  $utmData
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @return array{0: Donation, 1: list<string>}
     */
    public function handle(
        string $donorName,
        string $donorEmail,
        ?string $donorPhone,
        Money $amount,
        ?int $campaignId = null,
        bool $isAnonymous = false,
        ?string $message = null,
        array $utmData = [],
        ?string $ipAddress = null,
        array $items = [],
    ): array {
        if (! PaymentMethods::upiEnabled()) {
            throw new InvalidArgumentException('Direct UPI payment is not available right now.');
        }

        [$donation, $droppedProductNames] = $this->createPending->handle(
            donorName: $donorName,
            donorEmail: $donorEmail,
            donorPhone: $donorPhone,
            amount: $amount,
            campaignId: $campaignId,
            isAnonymous: $isAnonymous,
            message: $message,
            source: 'website_upi',
            utmData: $utmData,
            ipAddress: $ipAddress,
            items: $items,
        );

        $donation->update([
            'payment_mode' => PaymentMode::Upi->value,
            'is_offline' => true,
            // Assigned now, not at confirmation: this number is the UPI payment note, so it is
            // the one thing that ties a line on the bank statement back to this donation.
            'donation_number' => sprintf('DN-%s-%06d', $donation->financial_year, $donation->id),
        ]);

        return [$donation->fresh(), $droppedProductNames];
    }
}
