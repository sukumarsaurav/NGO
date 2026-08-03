<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChargeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per attempted billing cycle. A successful charge also creates a
 * linked `donations` row (`type = recurring`) — see
 * docs/modules/M06-recurring-autopay.md.
 *
 * @property ChargeStatus $status
 * @property Carbon $scheduled_for
 * @property Carbon|null $charged_at
 */
class SubscriptionCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'donation_id', 'cycle_number', 'amount', 'status',
        'provider_payment_id', 'scheduled_for', 'charged_at', 'failure_reason', 'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChargeStatus::class,
            'amount' => 'integer',
            'scheduled_for' => 'datetime',
            'charged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
