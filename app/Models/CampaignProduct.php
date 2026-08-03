<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A concrete needs-catalogue item: "Medicine kit · ₹900 · 11 of 1500 funded".
 * See docs/modules/M08-campaigns-crowdfunding.md's "Products — the needs
 * catalogue".
 *
 * `units_funded` is denormalised exactly like `campaigns.raised_amount` —
 * incremented on a succeeded donation, decremented on refund, and fully
 * recomputed by the nightly reconciler. Never sum `donation_items` on render.
 */
class CampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'name', 'description', 'image_path', 'unit_price',
        'units_needed', 'units_funded', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'units_needed' => 'integer',
            'units_funded' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<DonationItem, $this>
     */
    public function donationItems(): HasMany
    {
        return $this->hasMany(DonationItem::class);
    }

    public function percentFunded(): int
    {
        if ($this->units_needed <= 0) {
            return 0;
        }

        return (int) floor(($this->units_funded / $this->units_needed) * 100);
    }
}
