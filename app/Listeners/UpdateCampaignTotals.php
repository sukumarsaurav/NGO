<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DonationSucceeded;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;

/**
 * Fast increment path for a campaign's denormalised totals — see
 * docs/modules/M08-campaigns-crowdfunding.md. `App\Actions\Campaigns\
 * RecalculateCampaignTotals`, run nightly, is the source of truth that
 * corrects any drift this listener's failure (or a manual DB edit) causes.
 *
 * `donor_count` only increments the first time this donor has a *succeeded*
 * donation against this campaign — a repeat donor funding the same campaign
 * again must not inflate the donor count.
 *
 * Also increments each purchased `campaign_products.units_funded` — the
 * needs-catalogue equivalent of `raised_amount`, maintained the same way.
 */
final class UpdateCampaignTotals implements ShouldQueue
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(DonationSucceeded $event): void
    {
        $donation = $event->donation;

        if ($donation->campaign_id === null) {
            return;
        }

        $this->db->transaction(function () use ($donation) {
            $campaign = Campaign::query()->whereKey($donation->campaign_id)->lockForUpdate()->first();

            if (! $campaign) {
                return;
            }

            $isFirstSucceededForDonor = ! Donation::query()
                ->where('campaign_id', $campaign->id)
                ->where('donor_id', $donation->donor_id)
                ->where('status', 'succeeded')
                ->where('id', '!=', $donation->id)
                ->exists();

            $campaign->increment('raised_amount', $donation->amount);

            if ($isFirstSucceededForDonor) {
                $campaign->increment('donor_count');
            }

            foreach ($donation->items as $item) {
                CampaignProduct::query()->whereKey($item->campaign_product_id)->increment('units_funded', $item->quantity);
            }
        });
    }
}
