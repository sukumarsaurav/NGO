<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CampaignUpdateMail;
use App\Models\CampaignUpdate;
use App\Models\Donor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * "Publishing an update with `notify_donors = true` emails every donor to
 * that campaign — the delivery on the promise made at donation time." See
 * docs/modules/M08-campaigns-crowdfunding.md's "Campaign updates". Sends
 * once per donor even if they gave multiple times.
 */
final class NotifyCampaignDonorsOfUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $campaignUpdateId,
    ) {}

    public function handle(): void
    {
        $update = CampaignUpdate::query()->with('campaign')->findOrFail($this->campaignUpdateId);

        $donorIds = $update->campaign->donations()
            ->where('status', 'succeeded')
            ->distinct()
            ->pluck('donor_id');

        foreach (Donor::query()->whereIn('id', $donorIds)->get() as $donor) {
            Mail::to($donor->email)->queue(new CampaignUpdateMail($update, $donor->name));
        }
    }
}
