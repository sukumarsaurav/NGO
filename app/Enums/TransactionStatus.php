<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Created = 'created';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
