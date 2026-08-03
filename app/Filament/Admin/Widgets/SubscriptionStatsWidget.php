<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * MRR is the number leadership cares about most — see
 * docs/modules/M06-recurring-autopay.md's UI section: "Make it prominent."
 */
class SubscriptionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCount = Subscription::query()->where('status', SubscriptionStatus::Active->value)->count();

        $mrr = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where('interval', 'monthly')
            ->sum('amount');

        $haltedCount = Subscription::query()->where('status', SubscriptionStatus::Halted->value)->count();

        $churnedThisMonth = Subscription::query()
            ->where('status', SubscriptionStatus::Cancelled->value)
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();

        return [
            Stat::make('Active subscriptions', (string) $activeCount),
            Stat::make('Monthly recurring revenue', '₹'.number_format($mrr / 100, 2))
                ->description('Sum of active monthly mandates'),
            Stat::make('Halted — needs attention', (string) $haltedCount)
                ->color($haltedCount > 0 ? 'danger' : 'success'),
            Stat::make('Churned this month', (string) $churnedThisMonth),
        ];
    }
}
