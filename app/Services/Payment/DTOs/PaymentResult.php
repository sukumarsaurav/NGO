<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Support\Money;
use DateTimeImmutable;

/**
 * What the gateway reports about one payment attempt — the shape that feeds
 * `payment_transactions`. `fee`/`tax`/`netAmount` are what let bank
 * reconciliation work; see the "Fees and net amount" section of
 * docs/modules/M05-donations-payments.md.
 */
final class PaymentResult
{
    public readonly Money $fee;

    public readonly Money $tax;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $paymentId,
        public readonly ?string $orderId,
        public readonly string $status,
        public readonly Money $amount,
        ?Money $fee = null,
        ?Money $tax = null,
        public readonly ?string $method = null,
        public readonly ?string $bank = null,
        public readonly ?string $vpa = null,
        public readonly ?string $cardLast4 = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorDescription = null,
        public readonly ?DateTimeImmutable $capturedAt = null,
        public readonly array $raw = [],
    ) {
        $this->fee = $fee ?? Money::zero();
        $this->tax = $tax ?? Money::zero();
    }

    public function netAmount(): Money
    {
        return Money::fromPaise($this->amount->toPaise() - $this->fee->toPaise() - $this->tax->toPaise());
    }
}
