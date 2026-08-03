<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use App\Models\DonationItem;
use Illuminate\Database\ConnectionInterface;

/**
 * Full recompute of `raised_amount` / `donor_count` / every product's
 * `units_funded` from source — the source of truth behind
 * `UpdateCampaignTotals`'s fast increment path. Run nightly by
 * `App\Console\Commands\RecalculateAllCampaignTotals`, and available to
 * trigger by hand from the admin. See
 * docs/modules/M08-campaigns-crowdfunding.md: "Denormalised counters drift;
 * a listener that fails silently during a deploy leaves a campaign
 * under-reporting forever otherwise." `units_funded` "drifts for exactly the
 * same reasons `campaigns.raised_amount` does".
 */
final class RecalculateCampaignTotals
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    /**
     * @return bool whether any stored total (campaign or product) had drifted and was corrected
     */
    public function handle(Campaign $campaign): bool
    {
        return $this->db->transaction(function () use ($campaign) {
            $locked = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();

            $raised = (int) Donation::query()
                ->where('campaign_id', $locked->id)
                ->where('status', 'succeeded')
                ->sum('amount');

            $donorCount = (int) Donation::query()
                ->where('campaign_id', $locked->id)
                ->where('status', 'succeeded')
                ->distinct('donor_id')
                ->count('donor_id');

            $drifted = $locked->raised_amount !== $raised || $locked->donor_count !== $donorCount;

            if ($drifted) {
                $locked->update(['raised_amount' => $raised, 'donor_count' => $donorCount]);
            }

            $drifted = $this->recalculateProducts($locked) || $drifted;

            return $drifted;
        });
    }

    private function recalculateProducts(Campaign $campaign): bool
    {
        $drifted = false;

        foreach (CampaignProduct::query()->where('campaign_id', $campaign->id)->lockForUpdate()->get() as $product) {
            $unitsFunded = (int) DonationItem::query()
                ->where('campaign_product_id', $product->id)
                ->whereHas('donation', fn ($q) => $q->where('status', 'succeeded'))
                ->sum('quantity');

            if ($product->units_funded !== $unitsFunded) {
                $product->update(['units_funded' => $unitsFunded]);
                $drifted = true;
            }
        }

        return $drifted;
    }
}
