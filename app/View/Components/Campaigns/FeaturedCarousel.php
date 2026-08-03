<?php

declare(strict_types=1);

namespace App\View\Components\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Homepage section 2 ("Featured campaigns") per
 * docs/06-UI-UX-FOUNDATION.md's homepage section order — built in Sprint 10
 * so Sprint 11's homepage template can drop it straight in. Ordered by the
 * admin's drag-to-reorder `sort_order` (M08's "Featured-campaign carousel
 * management").
 */
class FeaturedCarousel extends Component
{
    public function __construct(
        public readonly int $limit = 6,
    ) {}

    public function render(): View
    {
        $campaigns = Campaign::query()
            ->where('is_featured', true)
            ->where('status', CampaignStatus::Active->value)
            ->with('category')
            ->orderBy('sort_order')
            ->limit($this->limit)
            ->get();

        return view('components.campaigns.featured-carousel', ['campaigns' => $campaigns]);
    }
}
