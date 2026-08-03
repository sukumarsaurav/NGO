<?php

declare(strict_types=1);

namespace App\Services\Numbering;

final class GeneratedReceiptNumber
{
    public function __construct(
        public readonly string $receiptNumber,
        public readonly int $sequenceNumber,
        public readonly string $financialYear,
    ) {}
}
