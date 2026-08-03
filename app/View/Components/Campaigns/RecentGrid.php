<?php

declare(strict_types=1);

namespace App\View\Components\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Homepage section 5 ("Recent campaigns grid") per
 * docs/06-UI-UX-FOUNDATION.md's homepage section order — built in Sprint 10
 * so Sprint 11's homepage template can drop it straight in.
 */
class RecentGrid extends Component
{
    public function __construct(
        public readonly int $limit = 6,
    ) {}

    public function render(): View
    {
        $campaigns = Campaign::query()
            ->where('status', CampaignStatus::Active->value)
            ->with('category')
            ->orderByDesc('created_at')
            ->limit($this->limit)
            ->get();

        return view('components.campaigns.recent-grid', ['campaigns' => $campaigns]);
    }
}
