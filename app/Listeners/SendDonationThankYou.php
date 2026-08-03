<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Receipts\Generate80GReceipt;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Events\DonationSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;

/**
 * Fires on every DonationSucceeded — see docs/modules/M05-donations-payments.md
 * step 8. Allocates the receipt number and row synchronously (fast, DB-only,
 * inside this queued listener); GenerateDonationReceipt itself dispatches the
 * PDF-render-and-email job. Auto-discovered by Laravel via the `handle`
 * type-hint — no explicit EventServiceProvider registration needed.
 *
 * Every donation gets the plain acknowledgement receipt. The 80G
 * certificate is attempted too, but a donor without PAN/address (or a
 * donation that simply isn't eligible — e.g. cash over ₹2,000) is not an
 * error here: they still got their acknowledgement, and can add PAN later
 * to trigger retroactive 80G issuance. See docs/modules/M07-receipts-80g.md's
 * "Donor adds PAN after donating" edge case.
 */
final class SendDonationThankYou implements ShouldQueue
{
    public function __construct(
        private readonly GenerateDonationReceipt $generateReceipt,
        private readonly Generate80GReceipt $generate80g,
    ) {}

    public function handle(DonationSucceeded $event): void
    {
        $this->generateReceipt->handle($event->donation);

        try {
            $this->generate80g->handle($event->donation);
        } catch (InvalidArgumentException) {
            // Not (yet) eligible — the donor still has their acknowledgement
            // receipt. See Generate80GReceipt::ineligibilityReason() for
            // surfacing the specific reason in the UI.
        }
    }
}
