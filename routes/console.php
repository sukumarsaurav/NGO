<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// See App\Console\Commands\AbandonStalePendingDonations and
// docs/06-UI-UX-FOUNDATION.md §6.
Schedule::command('donations:abandon-stale')->everyFiveMinutes();

// See App\Console\Commands\SyncSubscriptionStatus and
// docs/modules/M06-recurring-autopay.md's "Daily reconciliation" section.
Schedule::command('subscriptions:sync-status')->dailyAt('02:00');

// See App\Console\Commands\RecalculateAllCampaignTotals and
// docs/modules/M08-campaigns-crowdfunding.md's "nightly reconciler" section.
Schedule::command('campaigns:recalculate-totals')->dailyAt('03:00');

// See App\Console\Commands\PruneUnconfirmedSubscribers and
// docs/modules/M09-notices-communication.md's "Newsletter" section.
Schedule::command('newsletter:prune-unconfirmed')->dailyAt('04:00');

// See App\Console\Commands\SendMonthlySummary and
// docs/modules/M12-reports-analytics.md's "Scheduled reports" section.
Schedule::command('reports:monthly-summary')->monthlyOn(1, '08:00');

// Nightly DB dump + off-site copy — see docs/09-BACKUP-RESTORE.md and
// docs/03-ROADMAP.md's Sprint 15 "Backup strategy" line. `backup:clean`
// prunes per config/backup.php's retention policy before `backup:run`
// creates the new one, so cleanup never deletes the backup that's still
// running. `backup:monitor` after both checks freshness/size and notifies
// on config/backup.php's configured channel if anything looks wrong.
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('01:45')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('02:30');
