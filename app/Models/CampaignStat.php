<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-campaign impact counter ("5,000+ Dogs Rescued"). Distinct from the
 * site-wide `impact_stats` (M10) — these belong to one campaign. See
 * docs/02-DATABASE-SCHEMA.md §8.
 */
class CampaignStat extends Model
{
    protected $fillable = ['campaign_id', 'label', 'value', 'suffix', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
