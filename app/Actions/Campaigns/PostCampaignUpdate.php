<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Jobs\NotifyCampaignDonorsOfUpdate;
use App\Models\Campaign;
use App\Models\CampaignUpdate;

/**
 * Posts a "live impact update" against a campaign. See
 * docs/modules/M08-campaigns-crowdfunding.md.
 *
 * With `notify_donors = true` and the update published immediately, queues
 * `NotifyCampaignDonorsOfUpdate` to email every donor to this campaign once
 * — see "Campaign update → notify donors flow". An update scheduled for the
 * future doesn't notify here; nothing currently promotes it to "published"
 * later, so scheduled-update notification is a known gap, not silently
 * broken — see the module doc's future-dated `published_at` handling.
 */
final class PostCampaignUpdate
{
    /**
     * @param  array{title: string, body: string, image_path?: string|null,
     *     published_at?: mixed, notify_donors?: bool}  $attributes
     */
    public function handle(Campaign $campaign, array $attributes, ?int $createdByUserId = null): CampaignUpdate
    {
        $attributes['campaign_id'] = $campaign->id;
        $attributes['created_by_user_id'] = $createdByUserId;
        $attributes['published_at'] ??= now();

        $update = CampaignUpdate::query()->forceCreate($attributes);

        if ($update->notify_donors && $update->published_at !== null && $update->published_at->isPast()) {
            NotifyCampaignDonorsOfUpdate::dispatch($update->id);
        }

        return $update;
    }
}
