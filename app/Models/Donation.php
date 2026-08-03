<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\PaymentMode;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * What the donor intended to give — the business record. See
 * docs/02-DATABASE-SCHEMA.md §5. Never deleted; mistakes are corrected with
 * `status = cancelled` plus a note.
 *
 * @property DonationType $type
 * @property PaymentMode|null $payment_mode
 * @property DonationStatus $status
 * @property Carbon|null $donated_at
 * @property-read int|null $gateway_fee only present when selected via a join, e.g. ReportQueries::donationLedger()
 * @property-read int|null $net_amount only present when selected via a join, e.g. ReportQueries::donationLedger()
 */
class Donation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'donation_number', 'donor_id', 'campaign_id', 'subscription_id',
        'amount', 'items_amount', 'free_amount', 'currency', 'type', 'payment_mode',
        'status', 'is_offline', 'donated_at', 'financial_year', 'eligible_for_80g',
        'message', 'dedicated_to', 'source', 'utm_data', 'ip_address',
        'recorded_by_user_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => DonationType::class,
            'payment_mode' => PaymentMode::class,
            'status' => DonationStatus::class,
            'amount' => 'integer',
            'items_amount' => 'integer',
            'free_amount' => 'integer',
            'is_offline' => 'boolean',
            'donated_at' => 'datetime',
            'eligible_for_80g' => 'boolean',
            'utm_data' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /**
     * @return HasMany<DonationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DonationItem::class);
    }

    public function isSucceeded(): bool
    {
        return $this->status === DonationStatus::Succeeded;
    }
}
