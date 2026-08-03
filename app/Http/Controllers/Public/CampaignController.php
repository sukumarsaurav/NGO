<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CampaignFaq;
use App\Models\Redirect;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `/campaigns`, `/causes/{category}`, `/campaigns/{slug}` — see
 * docs/modules/M08-campaigns-crowdfunding.md and docs/07-SEO.md §1.
 *
 * Category pages live under `/causes/`, never `/campaigns/` — the two
 * namespaces are kept separate on purpose so a campaign slug can never
 * shadow a category slug or vice versa (07-SEO.md's "route shadowing" note).
 */
class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();
        $page = (int) $request->integer('page', 1);

        $campaigns = $this->paginateCached(
            "campaigns.index.{$sort}.{$page}",
            function () use ($sort) {
                $query = Campaign::query()->whereIn('status', $this->publiclyVisibleStatuses());

                return match ($sort) {
                    'most-funded' => $query->orderByDesc('raised_amount'),
                    'ending-soon' => $query->whereNotNull('ends_at')->orderBy('ends_at'),
                    'urgent' => $query->orderByDesc('is_urgent')->orderByDesc('created_at'),
                    default => $query->orderByDesc('created_at'),
                };
            },
            $request,
            $page,
        );

        return view('public.campaigns.index', [
            'campaigns' => $campaigns,
            'sort' => $sort,
        ]);
    }

    /**
     * `/monthly-giving` — campaigns accepting recurring gifts. See
     * docs/modules/M08-campaigns-crowdfunding.md's "UI" section and
     * docs/03-ROADMAP.md's Sprint 10 acceptance criterion: "shows only
     * recurring-enabled campaigns."
     */
    public function monthlyGiving(Request $request): View
    {
        $page = (int) $request->integer('page', 1);

        $campaigns = $this->paginateCached(
            "campaigns.monthly-giving.{$page}",
            fn () => Campaign::query()
                ->where('allows_recurring', true)
                ->where('status', CampaignStatus::Active->value)
                ->orderByDesc('created_at'),
            $request,
            $page,
        );

        return view('public.campaigns.monthly-giving', ['campaigns' => $campaigns]);
    }

    public function showCategory(string $slug, Request $request): View
    {
        $category = CampaignCategory::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $page = (int) $request->integer('page', 1);

        $campaigns = $this->paginateCached(
            "campaigns.category.{$category->id}.{$page}",
            fn () => Campaign::query()
                ->where('category_id', $category->id)
                ->whereIn('status', $this->publiclyVisibleStatuses())
                ->orderByDesc('created_at'),
            $request,
            $page,
        );

        return view('public.campaigns.category', [
            'category' => $category,
            'campaigns' => $campaigns,
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $campaign = Campaign::query()->where('slug', $slug)->with([
            'category',
            'updates' => function ($q) {
                $q->whereNotNull('published_at')->where('published_at', '<=', now())->orderByDesc('published_at');
            },
            'products' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'stats' => fn ($q) => $q->orderBy('sort_order'),
        ])->first();

        if (! $campaign || ! $campaign->status->isPubliclyVisible()) {
            // /campaigns/{slug} is a wildcard route, so Route::fallback() never
            // fires for a renamed campaign's old slug — it "matches" the route
            // syntactically and lands here instead. Check the redirect table
            // ourselves before giving up. See docs/07-SEO.md §7.
            $redirect = Redirect::query()->where('from_path', "/campaigns/{$slug}")->first();

            if ($redirect) {
                $redirect->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

                return redirect($redirect->to_path, $redirect->status_code === 301 ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
            }

            throw new NotFoundHttpException;
        }

        $donorWall = $campaign->donations()
            ->where('status', 'succeeded')
            ->with('donor')
            ->latest('donated_at')
            ->take(50)
            ->get()
            ->map(fn ($donation) => [
                'name' => $donation->donor->is_anonymous ? 'Anonymous' : $donation->donor->name,
                'amount' => $donation->amount,
                'donated_at' => $donation->donated_at,
            ]);

        $relatedCampaigns = Campaign::query()
            ->where('category_id', $campaign->category_id)
            ->where('id', '!=', $campaign->id)
            ->whereIn('status', $this->publiclyVisibleStatuses())
            ->take(4)
            ->get();

        $faqs = CampaignFaq::query()
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('campaign_id')->orWhere('campaign_id', $campaign->id))
            ->orderBy('campaign_id')
            ->orderBy('sort_order')
            ->get();

        return view('public.campaigns.show', [
            'campaign' => $campaign,
            'recentDonors' => $donorWall->sortByDesc('donated_at')->values(),
            'topDonors' => $donorWall->sortByDesc('amount')->values(),
            'relatedCampaigns' => $relatedCampaigns,
            'faqs' => $faqs,
        ]);
    }

    /**
     * Sprint 15 "Cache: ... campaign listings" — a public, high-traffic page
     * that doesn't need per-request freshness; 2 minutes bounds staleness
     * after a campaign's raised_amount or status changes, without needing
     * event-based invalidation for every donation.
     *
     * Only the ordered list of IDs and the total count are cached — never
     * the Eloquent models/paginator themselves. This app's
     * `config('cache.serializable_classes')` is `false` (a deliberate
     * security default: it makes `unserialize()` refuse to reconstruct ANY
     * object, converting cached Models/Paginators into broken
     * `__PHP_Incomplete_Class` instances instead — see the 500 this caused
     * on `/monthly-giving`). IDs and an int total are plain, safely
     * serializable data; the actual rows are re-fetched by primary key on
     * every request (cheap — indexed — and still skips the expensive
     * filter/sort/status query on a cache hit).
     *
     * @param  \Closure(): Builder<Campaign>  $queryFactory
     */
    private function paginateCached(string $cacheKey, \Closure $queryFactory, Request $request, int $page, int $perPage = 12): LengthAwarePaginatorContract
    {
        $cached = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($queryFactory, $page, $perPage) {
            $paginator = $queryFactory()->paginate($perPage, ['id'], 'page', $page);

            return [
                'ids' => $paginator->pluck('id')->all(),
                'total' => $paginator->total(),
            ];
        });

        $models = Campaign::query()->whereIn('id', $cached['ids'])->with('category')->get()->keyBy('id');
        $items = collect($cached['ids'])->map(fn (int $id) => $models->get($id))->filter()->values();

        return new LengthAwarePaginator($items, $cached['total'], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function publiclyVisibleStatuses(): array
    {
        return array_values(array_map(
            fn (CampaignStatus $status) => $status->value,
            array_filter(CampaignStatus::cases(), fn (CampaignStatus $status) => $status->isPubliclyVisible())
        ));
    }
}
