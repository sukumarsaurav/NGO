<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Receipt;
use App\Support\FinancialYear;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ReceiptSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $fy = FinancialYear::current()->toString();

        $counts = Cache::remember("dashboard.receipt_summary.{$fy}", now()->addMinutes(5), function () use ($fy) {
            return [
                'general' => Receipt::query()->where('financial_year', $fy)->where('series', 'donation')->where('is_cancelled', false)->count(),
                '80g' => Receipt::query()->where('financial_year', $fy)->where('series', '80g')->where('is_cancelled', false)->count(),
                'cancelled' => Receipt::query()->where('financial_year', $fy)->where('is_cancelled', true)->count(),
            ];
        });

        return [
            Stat::make('Donation receipts issued', (string) $counts['general'])->description("FY {$fy}"),
            Stat::make('80G receipts issued', (string) $counts['80g'])->description("FY {$fy}"),
            Stat::make('Cancelled receipts', (string) $counts['cancelled'])->color($counts['cancelled'] > 0 ? 'warning' : 'success'),
        ];
    }
}
