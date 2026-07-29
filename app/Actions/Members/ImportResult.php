<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Models\Member;

final class ImportResult
{
    /**
     * @param  list<ImportRowError>  $errors
     * @param  list<Member>  $created
     */
    public function __construct(
        public readonly int $totalRows,
        public readonly array $errors,
        public readonly array $created = [],
        public readonly bool $dryRun = true,
    ) {}

    public function validCount(): int
    {
        return $this->totalRows - $this->errorRowCount();
    }

    public function errorRowCount(): int
    {
        return collect($this->errors)->pluck('row')->unique()->count();
    }
}
