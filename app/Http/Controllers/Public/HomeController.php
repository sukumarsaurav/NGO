<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\ImpactStat;
use App\Models\PressMention;
use App\Models\Testimonial;
use Illuminate\View\View;

/**
 * The homepage — twelve sections, each naming its own storage. See
 * docs/modules/M10-public-site-cms.md's "Homepage sections".
 */
class HomeController extends Controller
{
    public function show(): View
    {
        $banners = Banner::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Banner $banner) => $banner->isCurrentlyScheduled())
            ->values();

        $featuredCampaigns = Campaign::query()
            ->where('is_featured', true)
            ->where('status', CampaignStatus::Active->value)
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        // No featured campaigns → fall back to the most recent active ones,
        // never an empty carousel. See M08's "No featured campaigns" edge case.
        if ($featuredCampaigns->isEmpty()) {
            $featuredCampaigns = Campaign::query()
                ->where('status', CampaignStatus::Active->value)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        return view('public.home', [
            'banners' => $banners,
            'featuredCampaigns' => $featuredCampaigns,
            'impactStats' => ImpactStat::query()->orderBy('sort_order')->get(),
            'categories' => CampaignCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'recentCampaigns' => Campaign::query()
                ->where('status', CampaignStatus::Active->value)
                ->with('category')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
            'pressMentions' => PressMention::query()->where('is_published', true)->orderBy('sort_order')->get(),
            'testimonials' => Testimonial::query()->where('is_published', true)->orderBy('sort_order')->get(),
        ]);
    }
}
