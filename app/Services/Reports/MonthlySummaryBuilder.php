<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * "A monthly summary email to admins on the 1st: last month's total, donor
 * count, new recurring donors, churn, top campaign, and anything in
 * 'attention needed'. This is the report the founder will actually read,
 * because it arrives without being asked for." See
 * docs/modules/M12-reports-analytics.md.
 */
final class MonthlySummaryBuilder
{
    /**
     * @return array{month: string, total: int, donor_count: int, new_recurring_donors: int, churned_subscriptions: int, top_campaign: string, attention: array<string, int>}
     */
    public function build(?Carbon $forMonth = null): array
    {
        $month = ($forMonth ?? now()->subMonthNoOverflow())->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $total = (int) Donation::query()
            ->where('status', 'succeeded')
            ->whereBetween('donated_at', [$start, $end])
            ->sum('amount');

        $donorCount = Donation::query()
            ->where('status', 'succeeded')
            ->whereBetween('donated_at', [$start, $end])
            ->distinct('donor_id')
            ->count('donor_id');

        $newRecurringDonors = Subscription::query()
            ->whereBetween('started_at', [$start, $end])
            ->count();

        $churnedSubscriptions = Subscription::query()
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$start, $end])
            ->count();

        $topCampaign = Campaign::query()
            ->whereHas('donations', fn ($q) => $q->where('status', 'succeeded')->whereBetween('donated_at', [$start, $end]))
            ->withSum(['donations as month_raised' => fn ($q) => $q->where('status', 'succeeded')->whereBetween('donated_at', [$start, $end])], 'amount')
            ->orderByDesc('month_raised')
            ->first();

        $attention = app(AttentionNeededCounts::class)->get();

        return [
            'month' => $month->format('F Y'),
            'total' => $total,
            'donor_count' => $donorCount,
            'new_recurring_donors' => $newRecurringDonors,
            'churned_subscriptions' => $churnedSubscriptions,
            'top_campaign' => $topCampaign ? $topCampaign->title : '—',
            'attention' => $attention,
        ];
    }
}
