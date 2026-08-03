<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Donation;
use App\Support\FinancialYear;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * "Today / this month / this FY, each with a change vs the previous
 * period" — see docs/modules/M12-reports-analytics.md. 5-minute cache TTL
 * per the same doc: "A dashboard that takes six seconds is a dashboard
 * nobody opens."
 */
class DonationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $data = Cache::remember('dashboard.donation_stats', now()->addMinutes(5), fn () => $this->compute());

        return [
            Stat::make('Today', '₹'.number_format($data['today'] / 100, 2))
                ->description($this->change($data['today'], $data['yesterday']))
                ->color($data['today'] >= $data['yesterday'] ? 'success' : 'danger'),

            Stat::make('This month', '₹'.number_format($data['this_month'] / 100, 2))
                ->description($this->change($data['this_month'], $data['last_month']))
                ->color($data['this_month'] >= $data['last_month'] ? 'success' : 'danger'),

            Stat::make('This financial year', '₹'.number_format($data['this_fy'] / 100, 2))
                ->description($this->change($data['this_fy'], $data['last_fy']))
                ->color($data['this_fy'] >= $data['last_fy'] ? 'success' : 'danger'),
        ];
    }

    /**
     * @return array{today: int, yesterday: int, this_month: int, last_month: int, this_fy: int, last_fy: int}
     */
    private function compute(): array
    {
        $sumBetween = fn ($start, $end) => (int) Donation::query()
            ->where('status', 'succeeded')
            ->whereBetween('donated_at', [$start, $end])
            ->sum('amount');

        $thisFy = FinancialYear::current();
        $lastFy = FinancialYear::for($thisFy->startsAt()->subDay());

        return [
            'today' => $sumBetween(now()->startOfDay(), now()->endOfDay()),
            'yesterday' => $sumBetween(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'this_month' => $sumBetween(now()->startOfMonth(), now()->endOfMonth()),
            'last_month' => $sumBetween(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()),
            'this_fy' => $sumBetween($thisFy->startsAt(), $thisFy->endsAt()),
            'last_fy' => $sumBetween($lastFy->startsAt(), $lastFy->endsAt()),
        ];
    }

    private function change(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'New this period' : 'No change';
        }

        $percent = round((($current - $previous) / $previous) * 100, 1);

        return ($percent >= 0 ? '+' : '').$percent.'% vs previous period';
    }
}
