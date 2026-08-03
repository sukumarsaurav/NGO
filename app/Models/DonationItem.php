<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item: N units of one `campaign_products` row, at the price
 * snapshotted at donation time. See docs/02-DATABASE-SCHEMA.md §8.
 */
class DonationItem extends Model
{
    protected $fillable = ['donation_id', 'campaign_product_id', 'quantity', 'unit_price', 'line_total'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * @return BelongsTo<CampaignProduct, $this>
     */
    public function campaignProduct(): BelongsTo
    {
        return $this->belongsTo(CampaignProduct::class);
    }
}
