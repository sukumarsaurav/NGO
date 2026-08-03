<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Support\Money;

final class OrderResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $orderId,
        public readonly Money $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly array $raw = [],
    ) {}
}
