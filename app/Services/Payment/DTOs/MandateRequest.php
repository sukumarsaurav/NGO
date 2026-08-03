<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Support\Money;

/**
 * Recurring mandate creation — used starting M06. Included in the interface
 * now so the abstraction never needs a breaking change when M06 lands.
 */
final class MandateRequest
{
    public function __construct(
        public readonly string $donorEmail,
        public readonly string $donorName,
        public readonly ?string $donorPhone,
        public readonly Money $amount,
        public readonly string $frequency,
        public readonly int $totalCount,
    ) {}
}
