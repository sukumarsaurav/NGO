<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberGrowthWidget extends ChartWidget
{
    protected ?string $heading = 'New members per month';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = Cache::remember('dashboard.member_growth', now()->addMinutes(5), function () {
            $start = now()->subMonthsNoOverflow(11)->startOfMonth();

            $monthExpression = match (DB::connection()->getDriverName()) {
                'mysql' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "strftime('%Y-%m', created_at)",
            };

            // A plain array, not the Collection `pluck()` returns — caching
            // a Collection object hits PHP's __PHP_Incomplete_Class on
            // unserialize when the widget re-renders via a Livewire AJAX
            // request (a different autoload timing than the first render).
            return DB::table('members')
                ->where('created_at', '>=', $start)
                ->selectRaw("{$monthExpression} as month, count(*) as total")
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
            $totals[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'datasets' => [
                ['label' => 'New members', 'data' => $totals],
            ],
            'labels' => $labels,
        ];
    }
}
