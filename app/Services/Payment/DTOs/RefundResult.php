<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Support\Money;

final class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $refundId,
        public readonly string $paymentId,
        public readonly Money $amount,
        public readonly string $status,
        public readonly array $raw = [],
    ) {}
}
