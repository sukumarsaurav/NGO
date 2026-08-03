<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DonationRefunded;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;

/**
 * Mirror of `UpdateCampaignTotals` for the refund path — see
 * docs/modules/M08-campaigns-crowdfunding.md's "Refund decrements totals".
 * Only fires on a *full* refund (see `DonationRefunded`'s docblock); a
 * partial refund leaves the donation `succeeded` and the campaign total
 * untouched, matching how the nightly reconciler treats it too. Also
 * decrements `units_funded` on every purchased catalogue item — M08 is
 * explicit that a refund does not attempt to "un-buy" a specific kit, it
 * only decrements the counts.
 */
final class DecrementCampaignTotalsOnRefund implements ShouldQueue
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(DonationRefunded $event): void
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

            $anyRemainingSucceededForDonor = Donation::query()
                ->where('campaign_id', $campaign->id)
                ->where('donor_id', $donation->donor_id)
                ->where('status', 'succeeded')
                ->exists();

            $campaign->update([
                'raised_amount' => max(0, $campaign->raised_amount - $donation->amount),
                'donor_count' => $anyRemainingSucceededForDonor
                    ? $campaign->donor_count
                    : max(0, $campaign->donor_count - 1),
            ]);

            foreach ($donation->items as $item) {
                $product = CampaignProduct::query()->whereKey($item->campaign_product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->update(['units_funded' => max(0, $product->units_funded - $item->quantity)]);
                }
            }
        });
    }
}
