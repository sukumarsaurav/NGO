<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Enums\ReceiptSeries;
use App\Models\Receipt;

/**
 * Cancels the current live receipt (if not already cancelled) and issues a
 * fresh one — a new number, a new row at `revision = MAX(revision) + 1`.
 * See docs/modules/M07-receipts-80g.md: "Reissuing after cancellation
 * allocates a new number and writes a new row."
 */
final class ReissueReceipt
{
    public function __construct(
        private readonly CancelReceipt $cancelReceipt,
        private readonly GenerateDonationReceipt $generateDonationReceipt,
        private readonly Generate80GReceipt $generate80g,
    ) {}

    public function handle(Receipt $receipt, string $reason): Receipt
    {
        if (! $receipt->is_cancelled) {
            $this->cancelReceipt->handle($receipt, $reason);
        }

        $donation = $receipt->donation;

        return $receipt->series === ReceiptSeries::EightyG
            ? $this->generate80g->handle($donation)
            : $this->generateDonationReceipt->handle($donation);
    }
}
