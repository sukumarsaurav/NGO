<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The eight browse-by-cause tiles. `/causes/{slug}` — the most durable SEO
 * asset in the project. See docs/modules/M08-campaigns-crowdfunding.md.
 */
class CampaignCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'icon_path', 'description', 'intro_body',
        'meta_title', 'meta_description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'category_id');
    }
}
