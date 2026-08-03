@php
    $metaTitle = $campaign->meta_title ?: "{$campaign->title} — Donate Now | ".config('app.name');
    $metaDescription = $campaign->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($campaign->story), 155, '');
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $coverUrl = $campaign->cover_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($campaign->cover_image_path) : null;
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :og-image="$coverUrl" :noindex="! $campaign->status->isPubliclyVisible()" :title-is-complete="true">
    <div class="w-full max-w-6xl">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-content-muted">
            <a href="{{ url('/') }}" class="hover:text-link">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('campaigns.category', $campaign->category->slug) }}" class="hover:text-link">{{ $campaign->category->name }}</a>
            <span class="mx-1">/</span>
            <span>{{ $campaign->title }}</span>
        </nav>

        @if (in_array($campaign->status->value, ['completed', 'closed', 'paused'], true))
            <div class="mb-4 rounded-lg border border-line-divider bg-surface p-3 text-sm text-content-muted">
                @if ($campaign->status->value === 'completed')
                    This campaign has reached its goal and is no longer accepting new donations. Thank you to everyone who gave.
                @elseif ($campaign->status->value === 'paused')
                    This campaign is temporarily paused and not accepting donations right now.
                @else
                    This campaign is closed and no longer accepting donations.
                @endif
            </div>
        @endif

        {{-- Three grid items, and the donation card is ONE of them — it used to be rendered
             twice, once in the left column for mobile and once in the right column for
             desktop, with the other hidden by a breakpoint. That meant two live Livewire
             components on every campaign page (two snapshots, two network round trips per
             interaction, two copies of the donor's half-typed details) and, once the fields
             gained the `id`s their labels bind to, two of every id on the page — so every
             label pointed at the hidden copy.

             Instead: source order is title/image → card → the rest, which is exactly the
             mobile order, and on `lg` the explicit row/column placement lifts the card into
             the right-hand column spanning both rows. --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 lg:col-start-1 lg:row-start-1">
                <h1 class="mb-1 text-2xl font-bold text-content sm:text-3xl">{{ $campaign->title }}</h1>
                @if ($campaign->subtitle)
                    <p class="mb-1 text-content-muted">{{ $campaign->subtitle }}</p>
                @endif
                @if ($campaign->beneficiary_name)
                    <p class="mb-4 text-sm text-content-muted">by {{ $campaign->beneficiary_name }}</p>
                @endif

                <div class="aspect-video overflow-hidden rounded-lg bg-surface-muted">
                    @if ($coverUrl)
                        <img src="{{ $coverUrl }}" alt="{{ $campaign->cover_image_alt ?: $campaign->title }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-content-muted">{{ $campaign->category->name }}</div>
                    @endif
                </div>
            </div>

            <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2">
                {{-- `top-24` clears the 64px header with room to spare. --}}
                <div class="lg:sticky lg:top-24">
                    <x-campaigns.donation-card :campaign="$campaign" :percent="$percent" />
                </div>
            </div>

            <div class="lg:col-span-2 lg:col-start-1 lg:row-start-2">
                {{-- `top-16` clears the 64px sticky header; `z-sticky-nav` (100) is the
                     layer allocated to exactly this element in 08-DESIGN-SYSTEM §6. It sits
                     below `z-header` (200) deliberately — the header wins the overlap. --}}
                <nav class="sticky top-16 z-sticky-nav mb-6 flex flex-wrap gap-6 border-b border-line-divider bg-background py-2 text-sm font-medium text-content-muted">
                    @if ($campaign->products->isNotEmpty())
                        <a href="#products" class="hover:text-content">Products</a>
                    @endif
                    <a href="#story" class="hover:text-content">Story</a>
                    <a href="#updates" class="hover:text-content">Updates</a>
                    <a href="#donors" class="hover:text-content">Donors</a>
                    @if ($faqs->isNotEmpty())
                        <a href="#faq" class="hover:text-content">FAQ</a>
                    @endif
                </nav>

                {{--
                    Catalogue first (concrete asks convert), per
                    docs/06-UI-UX-FOUNDATION.md's campaign-page section order.
                    A campaign with no products renders no Products tab and no
                    empty section — see M08's "Campaign with no products" edge case.
                --}}
                @if ($campaign->products->isNotEmpty())
                    <section id="products" class="scroll-mt-[6.5rem] mb-12">
                        <h2 class="mb-4 text-xl font-bold text-content">Products</h2>
                        @livewire('campaigns.product-catalogue', ['campaignId' => $campaign->id])
                    </section>
                @endif

                <section class="mb-8 rounded-lg border border-line-divider bg-surface p-4">
                    <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-content-muted">Know your NGO</p>
                    <div class="flex flex-wrap gap-2">
                        <x-badge variant="trust">Verified</x-badge>
                        <x-badge variant="trust">100% fund transparency</x-badge>
                        @if ($campaign->is_tax_benefit)
                            <x-badge variant="trust">80G tax benefit</x-badge>
                        @endif
                    </div>
                </section>

                @if ($campaign->stats->isNotEmpty())
                    <section class="mb-12 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($campaign->stats as $stat)
                            <div class="rounded-lg border border-line-divider bg-surface p-3 text-center">
                                <p class="text-xl font-bold text-content">{{ $stat->value }}{{ $stat->suffix }}</p>
                                <p class="text-xs text-content-muted">{{ $stat->label }}</p>
                            </div>
                        @endforeach
                    </section>
                @endif

                <section id="story" class="scroll-mt-[6.5rem] prose prose-sm mb-12 max-w-none text-content">
                    <h2 class="mb-3 text-xl font-bold text-content">Story</h2>
                    {{-- The story is edited via Filament's RichEditor and stores
                         real HTML — rendered raw here, not escaped, or every
                         paragraph and bold word would show as literal markup.
                         Sanitized regardless of the trusted-admin source —
                         see the same note on portal/notices/show.blade.php. --}}
                    {!! str($campaign->story)->sanitizeHtml() !!}
                </section>

                <section id="updates" class="scroll-mt-[6.5rem] mb-12">
                    <h2 class="mb-4 text-xl font-bold text-content">Updates</h2>
                    @forelse ($campaign->updates as $update)
                        <article class="mb-4 border-l-2 border-action pl-4">
                            <p class="text-xs text-content-muted">{{ $update->published_at->format('d M Y') }}</p>
                            <h3 class="font-semibold text-content">{{ $update->title }}</h3>
                            <p class="text-sm text-content-muted">{{ \Illuminate\Support\Str::limit($update->body, 300) }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-content-muted">No updates yet — check back as this campaign progresses.</p>
                    @endforelse
                </section>

                <section id="donors" class="scroll-mt-[6.5rem] mb-12" x-data="{ tab: 'recent' }">
                    <h2 class="mb-4 text-xl font-bold text-content">Donors ({{ $campaign->donor_count }})</h2>
                    <div class="mb-3 flex gap-2 text-sm">
                        <button type="button" @click="tab = 'recent'" :class="tab === 'recent' ? 'bg-action text-action-on' : 'bg-surface text-content'" class="rounded-full px-3 py-1 font-medium">Recent</button>
                        <button type="button" @click="tab = 'generous'" :class="tab === 'generous' ? 'bg-action text-action-on' : 'bg-surface text-content'" class="rounded-full px-3 py-1 font-medium">Most Generous</button>
                    </div>

                    <ul x-show="tab === 'recent'" class="divide-y divide-line-divider rounded-lg border border-line-divider">
                        @forelse ($recentDonors as $donor)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <span class="text-content">{{ $donor['name'] }}</span>
                                <span class="font-semibold text-content">₹{{ number_format($donor['amount'] / 100) }}</span>
                            </li>
                        @empty
                            <li class="px-4 py-4 text-sm text-content-muted">Be the first to donate.</li>
                        @endforelse
                    </ul>
                    <ul x-show="tab === 'generous'" x-cloak class="divide-y divide-line-divider rounded-lg border border-line-divider">
                        @forelse ($topDonors as $donor)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <span class="text-content">{{ $donor['name'] }}</span>
                                <span class="font-semibold text-content">₹{{ number_format($donor['amount'] / 100) }}</span>
                            </li>
                        @empty
                            <li class="px-4 py-4 text-sm text-content-muted">Be the first to donate.</li>
                        @endforelse
                    </ul>
                </section>

                @if ($faqs->isNotEmpty())
                    <section id="faq" class="scroll-mt-[6.5rem] mb-12" x-data="{ open: null }">
                        <h2 class="mb-4 text-xl font-bold text-content">Frequently asked questions</h2>
                        <div class="divide-y divide-line-divider rounded-lg border border-line-divider">
                            @foreach ($faqs as $index => $faq)
                                <div>
                                    <button
                                        type="button"
                                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-content"
                                    >
                                        {{ $faq->question }}
                                        <span x-text="open === {{ $index }} ? '−' : '+'"></span>
                                    </button>
                                    <div x-show="open === {{ $index }}" x-cloak class="px-4 pb-3 text-sm text-content-muted">
                                        {{ $faq->answer }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($relatedCampaigns->isNotEmpty())
                    <section class="mb-12">
                        <h2 class="mb-4 text-xl font-bold text-content">Related campaigns</h2>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            @foreach ($relatedCampaigns as $related)
                                <x-campaigns.card :campaign="$related" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $campaign->category->name, 'item' => route('campaigns.category', $campaign->category->slug)],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $campaign->title, 'item' => route('campaigns.show', $campaign->slug)],
            ],
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
    @if ($faqs->isNotEmpty())
        {{-- Real, visible Q&A only — see docs/07-SEO.md §3: "Mark up only
             what renders." No Product/Offer markup anywhere on this page;
             the catalogue is presentation, not e-commerce. --}}
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
                ])->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
    @if ($campaign->status->acceptsDonations())
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'DonateAction',
                'name' => "Donate to {$campaign->title}",
                'url' => route('campaigns.show', $campaign->slug),
                'recipient' => ['@type' => 'NGO', 'name' => config('app.name')],
            ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</x-layout.public>
