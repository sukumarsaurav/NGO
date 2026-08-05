@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $orgName = $settings->get('org.name') ?: config('app.name');
    $homeTitle = "{$orgName} — Donate to Verified NGO Campaigns in India";
    $metaDescription = $settings->get('seo.meta_description') ?: "{$orgName} — donate to verified NGO campaigns in India with instant 80G tax-exemption receipts.";
    $steps = $settings->get('homepage.steps') ?: [];
    $firstBanner = $banners->first();

    // Icon for a "Browse by cause" tile, keyed by keyword match on the category name.
    // `icon_path` on the model lets an admin upload a custom icon later — this is only the
    // fallback for the common case where nothing has been uploaded, used once on this page,
    // so it stays a local lookup rather than a new component (05-CONVENTIONS' fourth-repeat
    // rule for extraction). Heroicons outline paths, per 08-DESIGN-SYSTEM §9. Covers exactly
    // the six categories CampaignCategorySeeder ships (see that file); an admin adding a
    // seventh gets the heart default rather than a missing icon.
    $causeIcon = function (string $name): string {
        $name = strtolower($name);
        return match (true) {
            str_contains($name, 'food') => 'M3 11h18a9 9 0 01-18 0z M8 3v3 M12 2v3 M16 3v3',
            str_contains($name, 'old age'), str_contains($name, 'elder') => 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-4v-6H9v6H4a1 1 0 01-1-1z',
            str_contains($name, 'child') => 'M9.75 9.75c0 1.036.84 1.875 1.875 1.875h.75a1.875 1.875 0 000-3.75h-.75A1.875 1.875 0 009.75 9.75zM12 3a9 9 0 100 18 9 9 0 000-18zm0 4.5v1.5m0 6v1.5m-3.75-3.75h7.5',
            str_contains($name, 'educat') => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 15.5c0 2.485-4.03 4.5-9 4.5s-9-2.015-9-4.5a12.083 12.083 0 012.84-4.922L12 14z',
            str_contains($name, 'health'), str_contains($name, 'medical') => 'M12 6v12m6-6H6m15 0a9 9 0 11-18 0 9 9 0 0118 0z',
            str_contains($name, 'cloth') => 'M2.25 8.25h19.5M4.5 8.25l1.5 12h12l1.5-12M8.25 8.25V6a3.75 3.75 0 117.5 0v2.25',
            default => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        };
    };
@endphp

<x-layout.public
    :title="$homeTitle"
    :description="$metaDescription"
    :title-is-complete="true"
>
    {{-- HERO SECTION — FULL WIDTH EDGE-TO-EDGE WITH BACKGROUND BLEND --}}
    <section class="relative w-screen overflow-hidden mb-12 -mt-12 bg-[#f8f6f0]" style="margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);">
        {{-- Full Width Background Image --}}
        <div class="absolute inset-0 z-0 pointer-events-none">
            <img
                src="{{ $firstBanner ? \Illuminate\Support\Facades\Storage::disk('public')->url($firstBanner->image_path) : asset('images/hero-volunteer.png') }}"
                alt="{{ $firstBanner->title ?? 'Volunteer helping elderly woman' }}"
                class="absolute inset-0 h-full w-full object-cover object-right"
            />
            {{-- Light warm overlay on left so text is crisp while the natural golden bokeh photo shows through --}}
            <div class="absolute inset-0 bg-gradient-to-r from-[#fcf9f2]/85 via-[#fcf9f2]/40 via-45% to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20 flex flex-col lg:flex-row items-center justify-between gap-10 min-h-[500px]">

            {{-- Left Column: Typography & CTAs --}}
            <div class="w-full lg:w-7/12 flex flex-col items-start">

                {{-- Subheading --}}
                <span class="text-base sm:text-lg font-bold text-brand-800 tracking-wide mb-2">
                    We are Social Activists
                </span>

                @if ($firstBanner)
                    {{-- Main Title --}}
                    <h1 class="font-heading text-3xl sm:text-5xl lg:text-6xl font-black text-gray-900 uppercase leading-[1.1] tracking-tight mb-4">
                        {{ $firstBanner->title }}
                    </h1>

                    {{-- Divider with Heart --}}
                    <div class="flex items-center gap-3 w-40 mb-6">
                        <div class="h-[2px] flex-1 bg-brand-800/30"></div>
                        <svg class="h-4 w-4 text-brand-800" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z"/>
                        </svg>
                        <div class="h-[2px] flex-1 bg-brand-800/30"></div>
                    </div>

                    @if ($firstBanner->subtitle)
                        {{-- Paragraph --}}
                        <p class="text-base sm:text-lg text-gray-700 max-w-lg mb-8 leading-relaxed">
                            {{ $firstBanner->subtitle }}
                        </p>
                    @endif

                    @if ($firstBanner->cta_label)
                        {{-- Action Button --}}
                        <div class="flex items-center">
                            <x-button :href="$firstBanner->url() ?? route('donate.show')" variant="accent" size="xl" :pill="true" class="uppercase tracking-wider">
                                {{ $firstBanner->cta_label }}
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </x-button>
                        </div>
                    @endif
                @else
                    {{-- Main Title --}}
                    <h1 class="font-heading text-3xl sm:text-5xl lg:text-6xl font-black text-gray-900 uppercase leading-[1.1] tracking-tight mb-4">
                        TOGETHER, <br/>
                        WE CAN BRING <br/>
                        <span class="text-brand-800">CHANGE</span>
                    </h1>

                    {{-- Divider with Heart --}}
                    <div class="flex items-center gap-3 w-40 mb-6">
                        <div class="h-[2px] flex-1 bg-brand-800/30"></div>
                        <svg class="h-4 w-4 text-brand-800" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z"/>
                        </svg>
                        <div class="h-[2px] flex-1 bg-brand-800/30"></div>
                    </div>

                    {{-- Paragraph --}}
                    <p class="text-base sm:text-lg text-gray-700 max-w-lg mb-8 leading-relaxed">
                        We work for the well-being of humanity by helping the underprivileged and spreading hope, love and care.
                    </p>

                    {{-- Action Button --}}
                    <div class="flex items-center">
                        <x-button :href="route('donate.show')" variant="accent" size="xl" :pill="true" class="uppercase tracking-wider">
                            Join Us in Making a Difference
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </x-button>
                    </div>
                @endif
            </div>

            {{-- Right Column: Floating Badge over image --}}
            <div class="w-full lg:w-5/12 flex justify-end items-end mt-6 lg:mt-0">
                <div class="bg-[#f5efe0]/95 border border-[#e2d5bd] rounded-2xl p-4 sm:p-5 shadow-xl rotate-[-2deg] flex flex-col items-center text-center max-w-[200px]">
                    <span class="text-xs sm:text-sm font-serif italic text-gray-700">Every Act of</span>
                    <span class="text-base sm:text-lg font-extrabold text-brand-900 leading-tight">Kindness Counts</span>
                    <svg class="h-4 w-4 text-brand-800 mt-1" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <div class="w-full max-w-container">

        {{-- 2. FEATURED CAMPAIGNS --}}
        @if ($featuredCampaigns->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-4 text-2xl font-bold text-content">Featured Campaigns</h2>
                <div class="flex gap-4 overflow-x-auto pb-2">
                    @foreach ($featuredCampaigns as $campaign)
                        <div class="w-[18rem] flex-shrink-0"><x-campaigns.card :campaign="$campaign" /></div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 3. IMPACT STATS — light bordered/shadowed cards on the page background, not a
             dark banded promo. Matches the hover-lift pattern already used for the "Browse
             by Cause" tiles just below (§4) for visual consistency within this codebase,
             rather than inventing a new hover treatment. Stat tiles stay width-locked
             (`min-w-[7rem]`) to avoid CLS if a count-up animation is ever added on top. --}}
        @if ($impactStats->isNotEmpty())
            <section class="mb-12 text-center">
                <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-brand-600">Together we can</p>
                <h2 class="mb-2 font-heading text-2xl font-bold text-content sm:text-3xl">Every contribution creates change</h2>
                <p class="mx-auto mb-8 max-w-xl text-content-muted">Your support turns directly into food, medicine, school fees and shelter — with a receipt to prove it.</p>

                {{-- Tailwind's scanner only detects LITERAL class strings in source, so the
                     column count must be one of a fixed set it can actually see — not an
                     interpolated `sm:grid-cols-{{ $n }}`, which would never get generated. --}}
                @php
                    $statColsClass = match (min(4, max(1, $impactStats->count()))) {
                        1 => 'sm:grid-cols-1',
                        2 => 'sm:grid-cols-2',
                        3 => 'sm:grid-cols-3',
                        default => 'sm:grid-cols-4',
                    };
                @endphp
                <div class="mb-8 grid grid-cols-2 gap-4 {{ $statColsClass }}">
                    @foreach ($impactStats as $stat)
                        <div class="min-w-[7rem] rounded-lg border border-line-divider bg-surface p-4 text-center shadow-sm transition-shadow duration-base hover:-translate-y-px hover:shadow-md sm:p-6">
                            <p class="tabular font-heading text-2xl font-bold text-brand-700 sm:text-3xl">{{ $stat->value }}{{ $stat->suffix }}</p>
                            <p class="text-xs uppercase tracking-wide text-content-muted">{{ $stat->label }}</p>
                        </div>
                    @endforeach
                </div>

                <x-button variant="accent" size="lg" :pill="true" :href="route('donate.show')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z" />
                    </svg>
                    Donate Now
                </x-button>
            </section>
        @endif

        {{-- 4. BROWSE BY CAUSE — icon + description tile per §10.7 Category tile. Icon chip
             uses the semantic `success-bg`/`success-text` pair (brand-50/brand-700, 8.60:1)
             rather than a raw primitive — it's a measured, named token, just reused here for
             its colour rather than its "success" meaning. --}}
        @if ($categories->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-4 text-2xl font-bold text-content">Browse by Cause</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($categories as $category)
                        <a
                            href="{{ route('campaigns.category', $category->slug) }}"
                            class="group flex flex-col items-center gap-3 rounded-lg border border-line-divider bg-surface p-6 text-center transition-shadow duration-base hover:-translate-y-px hover:shadow-md"
                        >
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-success-bg text-success-text transition-colors duration-fast group-hover:bg-action group-hover:text-action-on">
                                @if ($category->icon_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->icon_path) }}" alt="" class="h-8 w-8 object-contain" aria-hidden="true">
                                @else
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $causeIcon($category->name) }}" />
                                    </svg>
                                @endif
                            </span>
                            <p class="font-semibold text-content">{{ $category->name }}</p>
                            @if ($category->description)
                                <p class="line-clamp-2 text-xs text-content-muted">{{ $category->description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 5. RECENT CAMPAIGNS --}}
        <section class="mb-12">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-content">Recent Campaigns</h2>
                <a href="{{ route('campaigns.index') }}" class="text-sm font-medium text-link hover:text-link-hover">View more</a>
            </div>
            @if ($recentCampaigns->isEmpty())
                <x-empty-state title="No live campaigns">
                    No campaigns are live right now — check back soon.
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentCampaigns as $campaign)
                        <x-campaigns.card :campaign="$campaign" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- 6. WHO WE SERVE --}}
        @if ($settings->get('homepage.serve_heading') || $settings->get('homepage.serve_body'))
            <section class="mb-12 grid grid-cols-1 items-center gap-6 rounded-lg bg-surface p-6 sm:grid-cols-2">
                @if ($settings->get('homepage.serve_image'))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->get('homepage.serve_image')) }}" alt="{{ $settings->get('homepage.serve_heading') }}" loading="lazy" class="w-full rounded-lg object-cover">
                @endif
                <div>
                    <h2 class="mb-2 text-2xl font-bold text-content">{{ $settings->get('homepage.serve_heading') }}</h2>
                    <p class="text-content-muted">{{ $settings->get('homepage.serve_body') }}</p>
                </div>
            </section>
        @endif

        {{-- 7. MONTHLY DONATION PROMO — full-bleed band, centered text, single pill CTA, on a
             dark-tinted background photo — the "Your Time is as Valuable as Your Money"
             volunteer-CTA pattern from the reference site, adopted here since this section
             is the closest structural match on this page (centered heading + subtext +
             single CTA, no other content competing). --}}
        @if ($settings->get('homepage.monthly_heading') || $settings->get('homepage.monthly_body'))
            <section class="relative mb-12 w-screen overflow-hidden px-6 py-12 text-center sm:py-16" style="margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);">
                <img
                    src="{{ asset('images/hero-volunteer.png') }}"
                    alt=""
                    aria-hidden="true"
                    loading="lazy"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-brand-900/80"></div>
                <div class="relative">
                    <h2 class="mb-2 font-heading text-2xl font-bold text-white sm:text-3xl">{{ $settings->get('homepage.monthly_heading') }}</h2>
                    <p class="mx-auto mb-6 max-w-xl text-brand-100">{{ $settings->get('homepage.monthly_body') }}</p>
                    <x-button variant="accent" size="lg" :pill="true" :href="route('campaigns.monthly-giving')">Give Monthly</x-button>
                </div>
            </section>
        @endif

        {{-- 8. HOW TO DONATE --}}
        @if (count($steps) > 0)
            <section class="mb-12">
                <h2 class="mb-4 text-2xl font-bold text-content">How to Donate</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    @foreach ($steps as $i => $step)
                        <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                            <p class="mb-2 text-2xl font-bold text-action">{{ $i + 1 }}</p>
                            <p class="mb-1 font-semibold text-content">{{ $step['title'] ?? '' }}</p>
                            <p class="text-xs text-content-muted">{{ $step['body'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 9. FEATURED IN --}}
        @if ($pressMentions->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-4 text-center text-sm font-semibold uppercase tracking-wide text-content-muted">Featured In</h2>
                <div class="flex flex-wrap items-center justify-center gap-8">
                    @foreach ($pressMentions as $mention)
                        <a href="{{ $mention->url }}" target="_blank" rel="noopener" class="text-content-muted hover:text-content">
                            @if ($mention->logo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mention->logo_path) }}" alt="{{ $mention->outlet_name }}" loading="lazy" class="h-8 object-contain grayscale">
                            @else
                                {{ $mention->outlet_name }}
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 10. TESTIMONIALS --}}
        @if ($testimonials->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-4 text-2xl font-bold text-content">What Donors Say</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <div class="rounded-lg border border-line-divider bg-surface p-4">
                            <p class="mb-3 text-sm text-content-muted">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
                            <p class="text-sm font-semibold text-content">{{ $testimonial->name }}</p>
                            @if ($testimonial->location)
                                <p class="text-xs text-content-muted">{{ $testimonial->location }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 11. FROM THE BLOG — one row, links out to the full listing rather than
             trying to be it. Same card markup as blog/index.blade.php so the two never
             drift into two different "blog card" designs. --}}
        @if ($blogPosts->isNotEmpty())
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-content">From the Blog</h2>
                    <a href="{{ route('blog.index') }}" class="text-sm font-medium text-link hover:text-link-hover">View all</a>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    @foreach ($blogPosts as $post)
                        <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden rounded-lg border border-line-divider bg-surface">
                            <div class="aspect-video bg-surface-muted">
                                @if ($post->cover_image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover_image_path) }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div class="p-4">
                                @if ($post->category)
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-action">{{ $post->category->label() }}</p>
                                @endif
                                <p class="mb-1 font-semibold text-content">{{ $post->title }}</p>
                                @if ($post->excerpt)
                                    <p class="line-clamp-2 text-sm text-content-muted">{{ $post->excerpt }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 12. GALLERY — one row of thumbnails, links out to /gallery. --}}
        @if ($galleryPhotos->isNotEmpty())
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-content">Gallery</h2>
                    <a href="{{ route('gallery.index') }}" class="text-sm font-medium text-link hover:text-link-hover">View all</a>
                </div>
                <div class="grid grid-cols-3 gap-4 sm:grid-cols-6">
                    @foreach ($galleryPhotos as $photo)
                        <a href="{{ route('gallery.index') }}" class="block aspect-square overflow-hidden rounded-lg border border-line-divider bg-surface-muted">
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}"
                                alt="{{ $photo->title ?: 'Gallery photo' }}"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-base hover:scale-105"
                            >
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 13. PARTNERS — logo strip, same treatment as "Featured In" (§9) since both
             are trust-signal logo rows, just a different source. --}}
        @if ($partners->isNotEmpty())
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-content-muted">Our Partners</h2>
                    <a href="{{ route('partners.index') }}" class="text-sm font-medium text-link hover:text-link-hover">View all</a>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-8">
                    @foreach ($partners as $partner)
                        <a href="{{ route('partners.index') }}" aria-label="{{ $partner->name }}">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($partner->logo_path) }}" alt="{{ $partner->name }}" loading="lazy" class="h-8 object-contain grayscale transition-all duration-base hover:grayscale-0">
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 14. CERTIFICATES — compact trust list, links out to /certificates. --}}
        @if ($certificates->isNotEmpty())
            <section class="mb-12">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-content">Certificates &amp; Registrations</h2>
                    <a href="{{ route('certificates.index') }}" class="text-sm font-medium text-link hover:text-link-hover">View all</a>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($certificates as $certificate)
                        <a href="{{ route('certificates.index') }}" class="flex items-center gap-3 rounded-lg border border-line-divider bg-surface p-4 transition-shadow duration-base hover:shadow-md">
                            <svg class="h-6 w-6 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-content">{{ $certificate->title }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 15. CSR PARTNERSHIP &amp; INTERNSHIP — always-on CTA cards (not data-driven
             lists like the sections above), so no isNotEmpty() guard. --}}
        <section class="mb-12 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="rounded-lg border border-line-divider bg-surface p-6 text-center">
                <h2 class="mb-2 font-heading text-xl font-bold text-content">CSR Partnership</h2>
                <p class="mb-4 text-sm text-content-muted">Channel your organisation's CSR budget into measurable, verified impact.</p>
                <x-button variant="accent" size="lg" :pill="true" :href="route('csr-partnership.show')">Partner With Us</x-button>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-6 text-center">
                <h2 class="mb-2 font-heading text-xl font-bold text-content">Internship Program</h2>
                <p class="mb-4 text-sm text-content-muted">Gain real-world experience working on campaigns and community outreach.</p>
                <x-button variant="accent" size="lg" :pill="true" :href="route('internship.show')">Apply Now</x-button>
            </div>
        </section>

    </div>{{-- close container --}}

    {{-- The newsletter signup that used to live in a full-width banner here now lives in
         the site footer (components/layout/public-footer.blade.php) so it appears on every
         page, not just the homepage. --}}

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $orgName,
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/campaigns').'?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
</x-layout.public>
