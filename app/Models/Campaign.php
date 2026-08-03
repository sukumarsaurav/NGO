<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The crowdfunding unit. See docs/modules/M08-campaigns-crowdfunding.md.
 *
 * `raised_amount` and `donor_count` are denormalised — kept in sync by
 * `App\Listeners\UpdateCampaignTotals` (fast path) and
 * `App\Actions\Campaigns\RecalculateCampaignTotals` (source of truth, run
 * nightly). Never sum `donations` on render.
 *
 * @property CampaignStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class Campaign extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'slug', 'category_id', 'title', 'subtitle', 'beneficiary_name', 'story',
        'cover_image_path', 'cover_image_alt', 'video_url', 'goal_amount', 'raised_amount', 'donor_count',
        'offline_raised_amount', 'allows_recurring', 'is_tax_benefit', 'is_featured',
        'is_urgent', 'status', 'starts_at', 'ends_at', 'sort_order',
        'meta_title', 'meta_description', 'created_by_user_id',
    ];

    protected static function booted(): void
    {
        // A published slug is frozen — see docs/07-SEO.md §1. If a campaign
        // that has ever left `draft` gets its slug changed (by any writer:
        // Filament, an Action, tinker), record a redirect from the old path
        // to the new one so the shared/printed/indexed link keeps working.
        static::updating(function (Campaign $campaign): void {
            if (! $campaign->isDirty('slug')) {
                return;
            }

            $originalStatus = $campaign->getOriginal('status');
            $wasEverPublished = $originalStatus instanceof CampaignStatus
                ? $originalStatus !== CampaignStatus::Draft
                : $originalStatus !== CampaignStatus::Draft->value;

            if (! $wasEverPublished) {
                return;
            }

            $from = '/campaigns/'.$campaign->getOriginal('slug');
            $to = '/campaigns/'.$campaign->slug;

            // Guard against loops/chains: if another redirect already points
            // *to* our old path, repoint it straight at the new destination
            // instead of chaining. See docs/07-SEO.md §7.
            Redirect::query()->where('to_path', $from)->update(['to_path' => $to]);

            Redirect::query()->updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => 301]
            );
        });
    }

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'goal_amount' => 'integer',
            'raised_amount' => 'integer',
            'donor_count' => 'integer',
            'offline_raised_amount' => 'integer',
            'allows_recurring' => 'boolean',
            'is_tax_benefit' => 'boolean',
            'is_featured' => 'boolean',
            'is_urgent' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<CampaignCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * @return HasMany<CampaignUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class);
    }

    /**
     * @return HasMany<CampaignProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    /**
     * @return HasMany<CampaignStat, $this>
     */
    public function stats(): HasMany
    {
        return $this->hasMany(CampaignStat::class);
    }

    /**
     * @return HasMany<CampaignFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(CampaignFaq::class);
    }

    /** Displayed total = online + offline (cheques, bank transfers recorded manually). */
    public function displayedRaisedAmount(): int
    {
        return $this->raised_amount + $this->offline_raised_amount;
    }

    public function percentFunded(): int
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }

        return (int) floor(($this->displayedRaisedAmount() / $this->goal_amount) * 100);
    }

    public function acceptsDonations(): bool
    {
        return $this->status->acceptsDonations();
    }
}
