<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Command;

/**
 * "Unconfirmed subscribers are never emailed and are purged after 30 days" —
 * docs/modules/M09-notices-communication.md's "Newsletter" section.
 */
class PruneUnconfirmedSubscribers extends Command
{
    protected $signature = 'newsletter:prune-unconfirmed';

    protected $description = 'Deletes newsletter subscribers who never confirmed within 30 days';

    public function handle(): int
    {
        $deleted = Subscriber::query()
            ->whereNull('confirmed_at')
            ->where('created_at', '<=', now()->subDays(30))
            ->delete();

        $this->info("Pruned {$deleted} unconfirmed subscriber(s).");

        return self::SUCCESS;
    }
}
