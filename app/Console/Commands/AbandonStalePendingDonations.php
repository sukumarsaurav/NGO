<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Illuminate\Console\Command;

/**
 * `pending` older than 30 minutes → `abandoned`. Without this the donations
 * table fills with noise and the admin dashboard's pending count becomes
 * meaningless — see docs/06-UI-UX-FOUNDATION.md §6.
 */
class AbandonStalePendingDonations extends Command
{
    protected $signature = 'donations:abandon-stale';

    protected $description = 'Marks pending donations older than 30 minutes as abandoned';

    public function handle(): int
    {
        $count = Donation::query()
            ->where('status', DonationStatus::Pending->value)
            ->where('created_at', '<', now()->subMinutes(30))
            ->update(['status' => DonationStatus::Abandoned->value]);

        $this->info("Abandoned {$count} stale pending donation(s).");

        return self::SUCCESS;
    }
}
