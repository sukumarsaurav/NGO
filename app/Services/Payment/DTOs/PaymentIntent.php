<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Support\Money;

/**
 * What we ask the gateway to create an order for. Built entirely from
 * server-side validated data — see the "server-side amount validation" rule
 * in docs/modules/M05-donations-payments.md. Never constructed from a raw
 * client-submitted amount.
 */
final class PaymentIntent
{
    /**
     * @param  array<string, string>  $notes
     */
    public function __construct(
        public readonly string $receipt,
        public readonly Money $amount,
        public readonly string $currency,
        public readonly string $donorEmail,
        public readonly string $donorName,
        public readonly ?string $donorPhone = null,
        public readonly array $notes = [],
    ) {}
}
