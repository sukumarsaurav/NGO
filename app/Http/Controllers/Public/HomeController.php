<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Certificate;
use App\Models\GalleryPhoto;
use App\Models\ImpactStat;
use App\Models\Partner;
use App\Models\Post;
use App\Models\PressMention;
use App\Models\Testimonial;
use Illuminate\View\View;

/**
 * The homepage — each section names its own storage. See
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
            // One row each — the landing page teases these sections and links to
            // their own full page (/blog, /gallery, /partners, /certificates)
            // rather than trying to be the full listing itself.
            'blogPosts' => Post::query()
                ->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(),
            'galleryPhotos' => GalleryPhoto::query()->where('is_published', true)->orderBy('sort_order')->limit(6)->get(),
            'partners' => Partner::query()->where('is_published', true)->orderBy('sort_order')->limit(8)->get(),
            'certificates' => Certificate::query()->where('is_published', true)->orderBy('sort_order')->limit(4)->get(),
        ]);
    }
}
