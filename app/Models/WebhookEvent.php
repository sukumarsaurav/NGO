<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency guard — UNIQUE(provider, event_id) is what makes gateway
 * webhook replays safe. See docs/modules/M05-donations-payments.md.
 */
class WebhookEvent extends Model
{
    protected $fillable = [
        'provider', 'event_id', 'event_type', 'payload', 'status', 'error', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
