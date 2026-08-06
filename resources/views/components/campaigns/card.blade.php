{{--
    Campaign card — used on the homepage (featured rail + recent grid), `/campaigns`,
    `/causes/{category}`, and the campaign detail page's "Related campaigns". One `<a>`, not
    three — the cover image, title, and CTA all used to link to the same URL, contributing 3
    of the ~7 tab stops per card (7 × 12 cards ≈ half the homepage's 128 focusable elements).
    A screen-reader/keyboard user now reaches one stop per card, with the campaign title as
    its accessible name, via the stretched-link pattern (`after:absolute after:inset-0` on
    the title's `<a>`, everything else `pointer-events-none` where it would otherwise shadow
    it). See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §3.2 and
    docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md PR 2.2/D8.

    Share controls (WhatsApp/Facebook/X/copy-link) were also here, duplicating
    `<x-campaigns.share-block>` which already exists, better, on the detail page — sharing a
    campaign you have not opened yet is not a listing-card action. Removed rather than kept
    in sync in two places.

    `$attributes->merge()` on the root so callers (the featured rail, related campaigns) can
    add `class="h-full"` and the card fills its flex/grid cell instead of collapsing to its
    own content height — a card of different length than its siblings previously staggered
    its "Donate Now" button out of line with the row. See §2.6 of the same audit.
--}}
@props(['campaign'])

@php
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
@endphp

<div {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm transition-shadow duration-base hover:shadow-md']) }}>
    <div class="relative block aspect-[4/3] bg-surface-muted">
        @if ($campaign->cover_image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($campaign->cover_image_path) }}" alt="" class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center text-content-muted">{{ $campaign->category->name ?? 'Campaign' }}</div>
        @endif

        {{-- Overlay badges sit on the image, not inline below it — matches the
             reference layout's severity/category-at-a-glance pattern. `pointer-events-none`
             so they never intercept the stretched link beneath them. --}}
        <div class="pointer-events-none absolute inset-x-3 top-3 flex items-start justify-between gap-2">
            @if ($campaign->is_urgent)
                <x-badge variant="urgent" class="shadow-sm">Urgent</x-badge>
            @else
                <span></span>
            @endif

            @if ($campaign->category)
                <x-badge variant="info" class="shadow-sm">{{ $campaign->category->name }}</x-badge>
            @endif
        </div>
    </div>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="mb-1 line-clamp-2 font-heading font-bold text-content">
            {{-- The stretched link — this is the card's one and only tab stop.
                 `after:absolute after:inset-0` extends its hit area over the whole card. --}}
            <a href="{{ route('campaigns.show', $campaign->slug) }}#donate" class="after:absolute after:inset-0 group-hover:text-link">
                {{ $campaign->title }}
            </a>
        </h3>

        <div class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-content-muted">
            @if ($campaign->beneficiary_name)
                <span>by {{ $campaign->beneficiary_name }}</span>
            @endif
            @if ($campaign->is_tax_benefit)
                <x-badge variant="trust">Tax Benefit</x-badge>
            @endif
        </div>

        @if ($campaign->subtitle)
            <p class="mb-3 line-clamp-2 text-sm text-content-muted">{{ $campaign->subtitle }}</p>
        @endif

        <div class="mt-auto">
            <div class="mb-2 flex items-center justify-between text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Raised</p>
                    <p class="font-semibold text-content">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-content-muted">Goal</p>
                    <p class="font-semibold text-content">₹{{ number_format($campaign->goal_amount / 100) }}</p>
                </div>
            </div>

            <x-progress-bar class="mb-1" :percent="$percent" />

            <div class="mb-3 flex items-center justify-between text-xs text-content-muted">
                <span>{{ number_format($campaign->donor_count) }} donors</span>
                <span class="font-semibold text-content">{{ $percent }}% funded</span>
            </div>

            {{-- Presentational only — the click is handled by the stretched title link
                 above, so this is not itself a second, redundant tab stop. It used to say
                 "Donate Now" and link straight to the campaign page, promising payment and
                 delivering a page; the honest label plus the `#donate` anchor on the title
                 link now do what the label always claimed. See §2.4 of the audit. --}}
            <span
                aria-hidden="true"
                class="inline-flex min-h-touch w-full items-center justify-center gap-2 rounded-full bg-action px-4 py-2 text-base font-semibold text-action-on transition-colors duration-fast group-hover:bg-action-hover"
            >
                Support this campaign
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </span>
        </div>
    </div>
</div>
