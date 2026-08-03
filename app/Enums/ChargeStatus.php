<?php

declare(strict_types=1);

namespace App\Enums;

enum ChargeStatus: string
{
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Skipped => 'Skipped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Succeeded => 'success',
            self::Scheduled, self::Processing => 'warning',
            self::Failed => 'danger',
            self::Skipped => 'gray',
        };
    }
}
