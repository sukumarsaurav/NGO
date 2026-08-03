<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A "live impact update" posted against a campaign. See
 * docs/modules/M08-campaigns-crowdfunding.md.
 *
 * @property Carbon|null $published_at
 */
class CampaignUpdate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'campaign_id', 'title', 'body', 'image_path', 'published_at',
        'notify_donors', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'notify_donors' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }
}
