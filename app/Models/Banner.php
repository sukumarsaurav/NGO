<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The homepage hero — rotating, scheduled, optionally linked to a campaign.
 * See docs/06-UI-UX-FOUNDATION.md §7.
 */
class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'subtitle', 'image_path', 'mobile_image_path', 'cta_label', 'cta_url',
        'campaign_id', 'sort_order', 'is_published', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function url(): ?string
    {
        if ($this->campaign_id && $this->campaign) {
            return route('campaigns.show', $this->campaign->slug);
        }

        return $this->cta_url;
    }

    public function isCurrentlyScheduled(): bool
    {
        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
