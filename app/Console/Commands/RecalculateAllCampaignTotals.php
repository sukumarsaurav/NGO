<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Campaigns\RecalculateCampaignTotals;
use App\Models\Campaign;
use Illuminate\Console\Command;

/**
 * Nightly reconciler for `campaigns.raised_amount` / `donor_count` — the
 * fast increment path (`App\Listeners\UpdateCampaignTotals`) can drift from
 * a failed deploy, a stuck queue, or a manual DB edit; this recomputes from
 * source every night and logs any campaign it had to correct. See
 * docs/modules/M08-campaigns-crowdfunding.md: "The nightly reconciler is
 * not optional."
 */
class RecalculateAllCampaignTotals extends Command
{
    protected $signature = 'campaigns:recalculate-totals';

    protected $description = 'Recomputes raised_amount and donor_count for every campaign from source donations';

    public function handle(RecalculateCampaignTotals $recalculate): int
    {
        $corrected = 0;

        Campaign::query()->withTrashed()->each(function (Campaign $campaign) use ($recalculate, &$corrected) {
            if ($recalculate->handle($campaign)) {
                $corrected++;
                $this->warn("Corrected drift on campaign #{$campaign->id} ({$campaign->slug}).");
            }
        });

        $this->info("Reconciled totals for all campaigns; corrected {$corrected}.");

        return self::SUCCESS;
    }
}
