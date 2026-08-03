<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonthlyTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly donation trend';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $rows = Cache::remember('dashboard.monthly_trend', now()->addMinutes(5), function () {
            $start = now()->subMonthsNoOverflow(11)->startOfMonth();

            // Grammar differs by driver (sqlite locally, mysql on shared
            // hosting in production — see docs/01-ARCHITECTURE.md's hosting
            // constraints); month-truncation has no portable Query Builder
            // helper, so the raw expression is chosen per driver rather than
            // assuming one.
            $monthExpression = match (DB::connection()->getDriverName()) {
                'mysql' => "DATE_FORMAT(donated_at, '%Y-%m')",
                default => "strftime('%Y-%m', donated_at)",
            };

            // A plain array, not the Collection `pluck()` returns — caching
            // a Collection object hits PHP's __PHP_Incomplete_Class on
            // unserialize when the widget re-renders via a Livewire AJAX
            // request (a different autoload timing than the first render).
            return DB::table('donations')
                ->where('status', 'succeeded')
                ->where('donated_at', '>=', $start)
                ->selectRaw("{$monthExpression} as month, sum(amount) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->all();
        });

        $labels = [];
        $totals = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $totals[] = round(($rows[$key] ?? 0) / 100, 2);
        }

        return [
            'datasets' => [
                ['label' => 'Donations (₹)', 'data' => $totals],
            ],
            'labels' => $labels,
        ];
    }
}
