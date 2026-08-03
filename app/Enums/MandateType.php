<?php

declare(strict_types=1);

namespace App\Enums;

enum MandateType: string
{
    case UpiAutopay = 'upi_autopay';
    case Emandate = 'emandate';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::UpiAutopay => 'UPI Autopay',
            self::Emandate => 'e-Mandate (NACH)',
            self::Card => 'Card',
        };
    }

    /**
     * UPI Autopay caps at ₹15,000/txn without additional authentication —
     * see docs/modules/M06-recurring-autopay.md. Other mandate types have no
     * ceiling enforced here.
     */
    public function perTransactionCeilingPaise(): ?int
    {
        return match ($this) {
            self::UpiAutopay => 1_500_000,
            self::Emandate, self::Card => null,
        };
    }
}
