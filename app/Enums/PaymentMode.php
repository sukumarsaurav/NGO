<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMode: string
{
    case Upi = 'upi';
    case Card = 'card';
    case Netbanking = 'netbanking';
    case Wallet = 'wallet';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Upi => 'UPI',
            self::Card => 'Card',
            self::Netbanking => 'Net banking',
            self::Wallet => 'Wallet',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
            self::BankTransfer => 'Bank transfer',
            self::Other => 'Other',
        };
    }

    public function isOffline(): bool
    {
        return in_array($this, [self::Cash, self::Cheque, self::BankTransfer], true);
    }
}
