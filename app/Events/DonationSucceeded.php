<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Donation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a donation is durably `succeeded`. Future listeners
 * (GenerateReceiptForDonation, SendDonationThankYou, UpdateCampaignTotals —
 * see docs/modules/M05-donations-payments.md step 8) attach in later
 * sprints; none are registered yet.
 */
final class DonationSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Donation $donation,
    ) {}
}
