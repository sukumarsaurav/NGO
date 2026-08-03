<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

final class MandateResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $status,
        public readonly ?string $shortUrl = null,
        public readonly array $raw = [],
    ) {}
}
