<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Campaign;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CampaignProgressWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.campaign-progress';

    protected int|string|array $columnSpan = 1;

    /**
     * @return list<array{title: string, raised: int, goal: int, percent: int}>
     */
    public function campaigns(): array
    {
        return Cache::remember('dashboard.campaign_progress', now()->addMinutes(5), function () {
            return Campaign::query()
                ->where('status', 'active')
                ->orderByDesc('raised_amount')
                ->limit(5)
                ->get()
                ->map(fn (Campaign $campaign) => [
                    'title' => $campaign->title,
                    'raised' => $campaign->displayedRaisedAmount(),
                    'goal' => $campaign->goal_amount,
                    'percent' => $campaign->percentFunded(),
                ])
                ->all();
        });
    }
}
