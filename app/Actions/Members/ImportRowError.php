<?php

declare(strict_types=1);

namespace App\Actions\Members;

final class ImportRowError
{
    public function __construct(
        public readonly int $row,
        public readonly string $field,
        public readonly string $message,
    ) {}
}
