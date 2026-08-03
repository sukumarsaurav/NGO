<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use InvalidArgumentException;

/**
 * draft / pending_review → active. From this point on, `Campaign::booted()`
 * turns any further slug change into a redirect rather than a silent rename
 * — see docs/07-SEO.md §1 ("a published slug is frozen").
 */
final class PublishCampaign
{
    public function handle(Campaign $campaign): Campaign
    {
        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::PendingReview], true)) {
            throw new InvalidArgumentException(
                "Only a draft or pending-review campaign can be published; this one is '{$campaign->status->value}'."
            );
        }

        $campaign->update([
            'status' => CampaignStatus::Active,
            'starts_at' => $campaign->starts_at ?? now(),
        ]);

        return $campaign->fresh();
    }
}
