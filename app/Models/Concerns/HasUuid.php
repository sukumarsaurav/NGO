<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generates `uuid` on creation. Every model with a public-facing identity
 * (never expose `id` in a URL) uses this — see docs/01-ARCHITECTURE.md.
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }
}
