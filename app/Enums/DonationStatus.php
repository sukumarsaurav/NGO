<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * pending --(gateway)--> processing --captured--> succeeded --refund--> refunded
 *                                    \--failed---> failed
 *   pending --(sweeper, 30 min)-----------------> abandoned
 *   any     --(admin correction)------------------> cancelled
 *
 * See docs/modules/M05-donations-payments.md.
 */
enum DonationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
            self::Abandoned => 'Abandoned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Succeeded => 'success',
            self::Pending, self::Processing => 'warning',
            self::Failed, self::Cancelled => 'danger',
            self::Refunded, self::Abandoned => 'gray',
        };
    }
}
