<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\MonthlySummaryMail;
use App\Models\User;
use App\Services\Reports\MonthlySummaryBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Scheduled for the 1st of every month — see routes/console.php and
 * docs/modules/M12-reports-analytics.md: "the report the founder will
 * actually read, because it arrives without being asked for."
 */
class SendMonthlySummary extends Command
{
    protected $signature = 'reports:monthly-summary';

    protected $description = 'Emails super-admins and admins last month\'s donation summary and attention-needed items';

    public function handle(MonthlySummaryBuilder $builder): int
    {
        $summary = $builder->build();

        $admins = User::role(['super-admin', 'admin'])->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new MonthlySummaryMail($summary));
        }

        $this->info("Monthly summary for {$summary['month']} queued to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
