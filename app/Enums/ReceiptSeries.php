<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * `Donation` is the plain acknowledgment receipt every successful donation
 * gets (Sprint 6). `EightyG` is the statutory tax-exemption receipt, gated
 * on donor PAN + address completeness — built in Sprint 8 (M07). Kept as one
 * enum now so `receipts`/`receipt_sequences` never need a migration to add
 * the second series later.
 */
enum ReceiptSeries: string
{
    case Donation = 'donation';
    case EightyG = '80g';
}
