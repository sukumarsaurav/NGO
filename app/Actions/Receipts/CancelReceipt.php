<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Models\Receipt;
use InvalidArgumentException;

/**
 * Receipts are cancelled, never deleted — the number is retained and never
 * reused. See docs/modules/M07-receipts-80g.md's "Cancellation and reissue"
 * section: "a gap-free sequence with a cancelled entry is correct; a reused
 * number is not."
 */
final class CancelReceipt
{
    public function handle(Receipt $receipt, string $reason): Receipt
    {
        if ($receipt->is_cancelled) {
            throw new InvalidArgumentException('This receipt is already cancelled.');
        }

        $receipt->update([
            'is_cancelled' => true,
            'cancelled_reason' => $reason,
        ]);

        return $receipt->fresh();
    }
}
