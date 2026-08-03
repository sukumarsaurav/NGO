<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonorType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Who gave — separate from Member because most donors are not members.
 * See docs/02-DATABASE-SCHEMA.md §5 and docs/modules/M05-donations-payments.md.
 *
 * @property DonorType $donor_type
 * @property Carbon|null $first_donated_at
 * @property Carbon|null $last_donated_at
 */
class Donor extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'pan',
        'address_line1', 'address_line2', 'city', 'state', 'pincode', 'country',
        'donor_type', 'total_donated', 'donation_count',
        'first_donated_at', 'last_donated_at', 'is_anonymous', 'marketing_opt_in',
    ];

    protected function casts(): array
    {
        return [
            'donor_type' => DonorType::class,
            'pan' => 'encrypted',
            'total_donated' => 'integer',
            'donation_count' => 'integer',
            'first_donated_at' => 'datetime',
            'last_donated_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'marketing_opt_in' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Case-insensitive, trimmed — see the "donor deduplication" rule in
     * docs/modules/M05-donations-payments.md. Never dedupe by phone alone.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::query()->whereRaw('lower(email) = ?', [trim(mb_strtolower($email))])->first();
    }
}
