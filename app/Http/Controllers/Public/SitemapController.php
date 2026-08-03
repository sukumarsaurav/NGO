<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap as SitemapTag;
use Spatie\Sitemap\Tags\Url;

/**
 * A sitemap **index** at /sitemap.xml pointing at four children, not one
 * monolith — see docs/07-SEO.md §2. `lastmod` is always the real
 * `updated_at`. Only `active`/`completed`/`paused`/`closed` campaigns are
 * listed (completed campaigns stay in the sitemap as trust signals); draft
 * and pending_review never appear. `/portal`, `/admin`, `/verify` are never
 * generated here at all.
 *
 * Cached for 10 minutes rather than written to disk on a schedule —
 * "regenerated nightly and on publish" from the spec is satisfied by a
 * short TTL: any publish is reflected within 10 minutes, and the cache
 * spares the DB a full table scan on every crawler hit.
 */
class SitemapController extends Controller
{
    private const TTL = 600;

    public function index(): Response
    {
        $xml = Cache::remember('sitemap.index', self::TTL, function () {
            $index = SitemapIndex::create();

            foreach (['sitemap-pages.xml', 'sitemap-causes.xml', 'sitemap-campaigns.xml', 'sitemap-blog.xml'] as $child) {
                $index->add(SitemapTag::create(url($child)));
            }

            return $index->render();
        });

        return $this->xmlResponse($xml);
    }

    public function pages(): Response
    {
        $xml = Cache::remember('sitemap.pages', self::TTL, function () {
            $sitemap = Sitemap::create()
                ->add(Url::create(route('home'))->setPriority(1.0))
                ->add(Url::create(route('campaigns.index'))->setPriority(0.9))
                ->add(Url::create(route('campaigns.monthly-giving'))->setPriority(0.6))
                ->add(Url::create(route('fundraiser.show'))->setPriority(0.5))
                ->add(Url::create(route('donate.show'))->setPriority(0.9))
                ->add(Url::create(route('contact.show'))->setPriority(0.4))
                ->add(Url::create(route('blog.index'))->setPriority(0.6));

            foreach (Page::query()->where('is_published', true)->get() as $page) {
                $sitemap->add(Url::create(route('pages.show', $page->slug))->setLastModificationDate($page->updated_at)->setPriority(0.5));
            }

            return $sitemap->render();
        });

        return $this->xmlResponse($xml);
    }

    public function causes(): Response
    {
        $xml = Cache::remember('sitemap.causes', self::TTL, function () {
            $sitemap = Sitemap::create();

            foreach (CampaignCategory::query()->where('is_active', true)->get() as $category) {
                $sitemap->add(Url::create(route('campaigns.category', $category->slug))->setLastModificationDate($category->updated_at)->setPriority(0.8));
            }

            return $sitemap->render();
        });

        return $this->xmlResponse($xml);
    }

    public function campaigns(): Response
    {
        $xml = Cache::remember('sitemap.campaigns', self::TTL, function () {
            $sitemap = Sitemap::create();

            $visibleStatuses = array_values(array_map(
                fn (CampaignStatus $status) => $status->value,
                array_filter(CampaignStatus::cases(), fn (CampaignStatus $status) => $status->isPubliclyVisible())
            ));

            foreach (Campaign::query()->whereIn('status', $visibleStatuses)->get() as $campaign) {
                $sitemap->add(Url::create(route('campaigns.show', $campaign->slug))->setLastModificationDate($campaign->updated_at)->setPriority(0.7));
            }

            return $sitemap->render();
        });

        return $this->xmlResponse($xml);
    }

    public function blog(): Response
    {
        $xml = Cache::remember('sitemap.blog', self::TTL, function () {
            $sitemap = Sitemap::create();

            foreach (Post::query()->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now())->get() as $post) {
                $sitemap->add(Url::create(route('blog.show', $post->slug))->setLastModificationDate($post->updated_at)->setPriority(0.6));
            }

            return $sitemap->render();
        });

        return $this->xmlResponse($xml);
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
