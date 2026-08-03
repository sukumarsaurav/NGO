<?php

declare(strict_types=1);

namespace App\Enums;

enum DonorType: string
{
    case Individual = 'individual';
    case Company = 'company';
    case Trust = 'trust';
    case Huf = 'huf';
    case Foreign = 'foreign';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Company => 'Company',
            self::Trust => 'Trust',
            self::Huf => 'HUF',
            self::Foreign => 'Foreign',
        };
    }
}
