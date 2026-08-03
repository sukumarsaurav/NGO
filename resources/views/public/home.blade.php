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
                src="{{ asset('images/hero-volunteer.png') }}"
                alt="Volunteer helping elderly woman"
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

                {{-- Main Title --}}
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-gray-900 uppercase leading-[1.1] tracking-tight mb-4">
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
                    <a href="{{ route('donate.show') }}" class="inline-flex items-center gap-3 rounded-full bg-[#163d26] px-10 sm:px-12 py-3.5 sm:py-4 text-xs sm:text-sm font-extrabold text-white uppercase tracking-wider whitespace-nowrap shadow-md hover:bg-[#0f2e1c] transition-all duration-200">
                        JOIN US IN MAKING A DIFFERENCE
                        <svg class="h-4 w-4 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </a>
                </div>
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

        {{-- 3. IMPACT STATS — a banded promo banner, not a bare stat grid. `bg-brand-800` is a
             deliberate one-off reach into the primitive ramp rather than a semantic token:
             the palette has no "dark inverse surface" role, and defining one for a single
             page-level banner would be over-engineering. Stat tiles stay width-locked
             (`min-w-[7rem]`) to avoid CLS if a count-up animation is ever added on top. --}}
        @if ($impactStats->isNotEmpty())
            <section class="mb-12 overflow-hidden rounded-lg bg-brand-800 p-8 sm:p-12">
                <div class="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                    <div class="text-center lg:text-left">
                        <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-brand-200">Together we can</p>
                        <h2 class="mb-2 text-2xl font-bold text-white sm:text-3xl">Every contribution creates change</h2>
                        <p class="mb-6 text-brand-100">Your support turns directly into food, medicine, school fees and shelter — with a receipt to prove it.</p>
                        <x-button variant="accent" size="lg" :href="route('donate.show')" class="mx-auto lg:mx-0">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z" />
                            </svg>
                            Donate Now
                        </x-button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($impactStats as $stat)
                            <div class="min-w-[7rem] rounded-lg bg-white/10 p-4 text-center">
                                <p class="tabular text-2xl font-bold text-white">{{ $stat->value }}{{ $stat->suffix }}</p>
                                <p class="text-xs text-brand-100">{{ $stat->label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
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

        {{-- 7. MONTHLY DONATION PROMO --}}
        @if ($settings->get('homepage.monthly_heading') || $settings->get('homepage.monthly_body'))
            <section class="mb-12 rounded-lg bg-action/10 p-6 text-center">
                <h2 class="mb-2 text-2xl font-bold text-content">{{ $settings->get('homepage.monthly_heading') }}</h2>
                <p class="mb-4 text-content-muted">{{ $settings->get('homepage.monthly_body') }}</p>
                <x-button size="lg" :href="route('campaigns.monthly-giving')">Give Monthly</x-button>
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

        {{-- 11. NEWSLETTER / CTA BANNER — full-width dark strip with background image,
                 impact stats, donate CTA, and newsletter form.
                 Breaks out of the container to go edge-to-edge. --}}
    </div>{{-- close container so the banner can be full-width --}}

    <section class="mb-12 relative w-screen overflow-hidden" style="margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);">
        {{-- Background image --}}
        <img
            src="{{ asset('images/hero-community.png') }}"
            alt=""
            aria-hidden="true"
            loading="lazy"
            class="absolute inset-0 h-full w-full object-cover"
        >
        {{-- Dark overlay gradient --}}
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(16,61,38,0.45) 0%, rgba(16,61,38,0.82) 30%, rgba(11,41,26,0.95) 55%, rgba(11,41,26,0.97) 100%);"></div>

        {{-- Content row --}}
        <div class="relative mx-auto flex max-w-container flex-col items-center gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:gap-8">
            {{-- Left: Heading + subtext --}}
            <div class="shrink-0 text-center lg:text-left" style="max-width: 280px;">
                <h2 class="mb-2 text-xl font-bold text-white sm:text-2xl" style="line-height: 1.2; text-transform: uppercase; letter-spacing: 0.01em;">
                    Be the Reason Someone Smiles Today
                </h2>
                <p class="text-sm text-brand-200">
                    Your small contribution can make a big difference in someone's life.
                </p>
            </div>

            {{-- Center: Impact stats in a row --}}
            @if ($impactStats->isNotEmpty())
                <div class="flex flex-1 flex-wrap items-center justify-center gap-6 lg:gap-8">
                    @foreach ($impactStats->take(4) as $stat)
                        <div class="flex flex-col items-center gap-1 text-center" style="min-width: 80px;">
                            <span class="mb-1 flex h-10 w-10 items-center justify-center rounded-full" style="background: rgba(255,255,255,0.1);">
                                @if ($loop->index === 0)
                                    <svg class="h-5 w-5 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                @elseif ($loop->index === 1)
                                    <svg class="h-5 w-5 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                @elseif ($loop->index === 2)
                                    <svg class="h-5 w-5 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-accent-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                                    </svg>
                                @endif
                            </span>
                            <p class="text-xl font-bold text-white" style="font-variant-numeric: tabular-nums;">{{ $stat->value }}{{ $stat->suffix }}</p>
                            <p class="text-xs text-brand-200">{{ $stat->label }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Right: Donate CTA + tagline --}}
            <div class="flex shrink-0 flex-col items-center gap-3 text-center">
                <a href="{{ route('donate.show') }}" class="inline-flex items-center gap-2 rounded-sm px-6 py-2 text-sm font-bold transition-all duration-base" style="background: var(--accent-400); color: var(--brand-900); border: 2px solid var(--accent-400);" onmouseover="this.style.background='var(--accent-300)';this.style.borderColor='var(--accent-300)'" onmouseout="this.style.background='var(--accent-400)';this.style.borderColor='var(--accent-400)'">
                    DONATE NOW
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z" />
                    </svg>
                </a>
                <p class="text-xs italic text-accent-300" style="max-width: 150px;">Thank you for being a part of our journey!</p>
                <svg class="h-6 w-6 text-accent-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12.001 19.653l-1.34-1.22C5.4 13.86 2 10.77 2 6.98 2 4.24 4.24 2 6.98 2c1.55 0 3.04.72 4.02 1.85A5.34 5.34 0 0115.02 2C17.76 2 20 4.24 20 6.98c0 3.79-3.4 6.88-8.66 11.46l-1.33 1.21Z" />
                </svg>
            </div>
        </div>

        {{-- Newsletter form row below --}}
        <div class="relative mx-auto max-w-container px-4 pb-6 sm:px-6">
            @if (session('status'))
                <p class="mb-3 text-center text-sm text-accent-300">{{ session('status') }}</p>
            @endif
            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="mx-auto flex max-w-md gap-2">
                @csrf
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input type="email" id="newsletter-email" name="email" required placeholder="you@example.com" class="min-h-touch flex-1 rounded-sm border-0 px-3 py-2 text-base text-content placeholder:text-content-placeholder" style="background: rgba(255,255,255,0.15); color: #fff;">
                <button type="submit" class="inline-flex items-center rounded-sm px-4 py-2 text-sm font-bold transition-all duration-base" style="background: var(--accent-400); color: var(--brand-900);">Subscribe</button>
            </form>
            @error('email') <p class="mt-2 text-center text-xs" style="color: #fca5a5;">{{ $message }}</p> @enderror
        </div>
    </section>

    {{-- Reopen a dummy wrapper so the closing </div> at the bottom still pairs --}}
    <div>

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
