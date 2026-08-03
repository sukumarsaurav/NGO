<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Donor;
use App\Models\Receipt;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Shared by `AttentionNeededWidget` (dashboard) and `MonthlySummaryBuilder`
 * (email) — see docs/modules/M12-reports-analytics.md: "Never write the
 * aggregation logic twice; extract it and share it."
 */
final class AttentionNeededCounts
{
    /**
     * @return array{halted_subscriptions: int, failed_jobs: int, bounced_receipts: int, donors_missing_pan: int}
     */
    public function get(): array
    {
        return Cache::remember('dashboard.attention_needed', now()->addMinutes(5), function () {
            return [
                'halted_subscriptions' => Subscription::query()->where('status', 'halted')->count(),
                'failed_jobs' => DB::table('failed_jobs')
                    ->where('payload', 'like', '%GeneratePdfDocument%')
                    ->orWhere('payload', 'like', '%GenerateReceiptPdf%')
                    ->count(),
                'bounced_receipts' => Receipt::query()->where('email_status', 'bounced')->count(),
                'donors_missing_pan' => Donor::query()
                    ->whereNull('pan')
                    ->whereHas('donations', fn ($q) => $q->where('eligible_for_80g', true)->where('status', 'succeeded'))
                    ->count(),
            ];
        });
    }
}
