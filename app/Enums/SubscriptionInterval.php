<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;

enum SubscriptionInterval: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    public function addTo(Carbon $date): Carbon
    {
        return match ($this) {
            self::Monthly => $date->copy()->addMonthNoOverflow(),
            self::Quarterly => $date->copy()->addMonthsNoOverflow(3),
            self::Yearly => $date->copy()->addYear(),
        };
    }
}
