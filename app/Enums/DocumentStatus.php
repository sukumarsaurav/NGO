<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * queued --(GeneratePdfDocument job)--> issued --revoke--> revoked
 *                                          |
 *                                          +--reissue--> superseded (new row created, this one linked via superseded_by_id)
 */
enum DocumentStatus: string
{
    case Queued = 'queued';
    case Issued = 'issued';
    case Revoked = 'revoked';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Issued => 'Issued',
            self::Revoked => 'Revoked',
            self::Superseded => 'Superseded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Issued => 'success',
            self::Queued => 'warning',
            self::Revoked => 'danger',
            self::Superseded => 'gray',
        };
    }
}
