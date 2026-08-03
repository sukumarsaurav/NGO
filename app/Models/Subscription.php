<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CancelledBy;
use App\Enums\MandateType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A recurring donation mandate. See docs/02-DATABASE-SCHEMA.md §6 and
 * docs/modules/M06-recurring-autopay.md — `status` mirrors Razorpay's own
 * state machine exactly, never a simplified version of it.
 *
 * @property SubscriptionStatus $status
 * @property SubscriptionInterval $interval
 * @property MandateType|null $mandate_type
 * @property CancelledBy|null $cancelled_by
 * @property Carbon|null $started_at
 * @property Carbon|null $next_charge_at
 * @property Carbon|null $last_charged_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $cancelled_at
 * @property array<string, mixed>|null $raw_response
 */
class Subscription extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'donor_id', 'campaign_id', 'provider', 'provider_plan_id',
        'provider_subscription_id', 'provider_token_id', 'amount', 'interval',
        'total_cycles', 'completed_cycles', 'status', 'mandate_type',
        'started_at', 'next_charge_at', 'last_charged_at', 'ended_at',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'failed_charge_count', 'total_collected', 'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'interval' => SubscriptionInterval::class,
            'mandate_type' => MandateType::class,
            'cancelled_by' => CancelledBy::class,
            'amount' => 'integer',
            'total_collected' => 'integer',
            'started_at' => 'datetime',
            'next_charge_at' => 'datetime',
            'last_charged_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Donor, $this>
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    /**
     * @return HasMany<SubscriptionCharge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(SubscriptionCharge::class);
    }
}
