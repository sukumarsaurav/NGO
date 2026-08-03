<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A question in the FAQ accordion. A null `campaign_id` means a global FAQ
 * shown on every campaign page — see docs/02-DATABASE-SCHEMA.md §8.
 */
class CampaignFaq extends Model
{
    protected $fillable = ['campaign_id', 'question', 'answer', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
