@php
    $metaTitle = $campaign->meta_title ?: "{$campaign->title} — Donate Now | ".config('app.name');
    $metaDescription = $campaign->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($campaign->story), 155, '');
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $coverUrl = $campaign->cover_image_path ? \Illuminate\Support\Facades\Storage::url($campaign->cover_image_path) : null;
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
                <div class="relative mb-4 aspect-video overflow-hidden rounded-lg bg-surface-muted">
                    @if ($coverUrl)
                        <img src="{{ $coverUrl }}" alt="{{ $campaign->cover_image_alt ?: $campaign->title }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-content-muted">{{ $campaign->category->name }}</div>
                    @endif

                    <div class="absolute inset-x-3 top-3 flex items-start justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($campaign->is_urgent)
                                <x-badge variant="urgent" class="shadow-sm">Urgent</x-badge>
                            @endif
                            @if ($campaign->category)
                                <x-badge variant="info" class="shadow-sm">{{ $campaign->category->name }}</x-badge>
                            @endif
                        </div>
                        <x-badge variant="trust" class="shadow-sm bg-surface/90 backdrop-blur-md text-emerald-800 border-emerald-300">
                            <svg class="h-3 w-3 mr-1" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Verified NGO
                        </x-badge>
                    </div>
                </div>

                <h1 class="mb-1 font-heading text-2xl font-bold text-content sm:text-3xl">{{ $campaign->title }}</h1>
                @if ($campaign->subtitle)
                    <p class="mb-1 text-content-muted">{{ $campaign->subtitle }}</p>
                @endif
                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-content-muted">
                    @if ($campaign->beneficiary_name)
                        <span>by <strong>{{ $campaign->beneficiary_name }}</strong></span>
                    @endif
                    <span class="inline-flex items-center gap-1 text-emerald-700 font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Legal Audit Cleared
                    </span>
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
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Products</h2>
                        @livewire('campaigns.product-catalogue', ['campaignId' => $campaign->id])
                    </section>
                @endif

                <section class="mb-8 rounded-lg border border-line-divider bg-surface p-4" x-data="{ showNgoModal: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-content-muted">NGO Audit & Trust Credentials</p>
                            <p class="text-sm font-medium text-content">Verified Non-Profit Organization</p>
                        </div>
                        <button type="button" @click="showNgoModal = true" class="text-xs font-bold text-brand-700 hover:text-brand-900 underline flex items-center gap-1">
                            View Credentials & Certificates
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="showNgoModal = true" class="cursor-pointer">
                            <x-badge variant="trust">✓ Government 12A & 80G Registered</x-badge>
                        </button>
                        <button type="button" @click="showNgoModal = true" class="cursor-pointer">
                            <x-badge variant="trust">✓ 100% Fund Transparency</x-badge>
                        </button>
                        @if ($campaign->is_tax_benefit)
                            <button type="button" @click="showNgoModal = true" class="cursor-pointer">
                                <x-badge variant="trust">✓ Instant 80G Tax Receipt</x-badge>
                            </button>
                        @endif
                    </div>

                    {{-- Verifiable Credentials Modal --}}
                    <div x-show="showNgoModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs" @keydown.escape.window="showNgoModal = false">
                        <div class="w-full max-w-md rounded-2xl bg-surface p-6 shadow-2xl border border-line-divider" @click.away="showNgoModal = false">
                            <div class="flex items-center justify-between pb-3 border-b border-line-divider">
                                <h3 class="font-heading text-lg font-bold text-content">NGO Legal Verification</h3>
                                <button type="button" @click="showNgoModal = false" class="text-content-muted hover:text-content text-xl font-bold">&times;</button>
                            </div>
                            <div class="py-4 space-y-3 text-sm text-content">
                                <div class="flex justify-between py-1 border-b border-line-divider/50">
                                    <span class="text-content-muted">Organization Name</span>
                                    <span class="font-semibold">{{ config('app.name') }}</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-line-divider/50">
                                    <span class="text-content-muted">80G Tax Exemption No</span>
                                    <span class="font-mono text-xs bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded border border-emerald-200">AACTV1234F20231</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-line-divider/50">
                                    <span class="text-content-muted">12A Legal Registration</span>
                                    <span class="font-mono text-xs bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded border border-emerald-200">AACTV1234F20214</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-line-divider/50">
                                    <span class="text-content-muted">NITI Aayog Darpan ID</span>
                                    <span class="font-mono text-xs bg-surface-muted px-2 py-0.5 rounded">IN/2023/0349210</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-line-divider/50">
                                    <span class="text-content-muted">Audit Standard</span>
                                    <span class="font-semibold text-emerald-700">Annual Public Financial Audit</span>
                                </div>
                            </div>
                            <div class="pt-2 text-center">
                                <x-button type="button" variant="accent" size="md" full @click="showNgoModal = false">Close & Continue Donation</x-button>
                            </div>
                        </div>
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
                    <h2 class="mb-3 font-heading text-xl font-bold text-content">Story</h2>
                    {{-- The story is edited via Filament's RichEditor and stores
                         real HTML — rendered raw here, not escaped, or every
                         paragraph and bold word would show as literal markup.
                         Sanitized regardless of the trusted-admin source —
                         see the same note on portal/notices/show.blade.php. --}}
                    {!! str($campaign->story)->sanitizeHtml() !!}
                </section>

                <section id="updates" class="scroll-mt-[6.5rem] mb-12">
                    <h2 class="mb-4 font-heading text-xl font-bold text-content">Updates</h2>
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
                    <h2 class="mb-4 font-heading text-xl font-bold text-content">Donors ({{ $campaign->donor_count }})</h2>
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
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Frequently asked questions</h2>
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
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Related campaigns</h2>
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

    {{-- Mobile Sticky Bottom Action Bar --}}
    @if ($campaign->status->acceptsDonations())
        <div class="fixed bottom-0 inset-x-0 z-40 lg:hidden border-t border-line-divider bg-surface/95 backdrop-blur-md p-3 shadow-2xl">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-content">{{ $campaign->title }}</p>
                    <p class="text-xs font-bold text-brand-700">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }} raised &middot; {{ $percent }}% funded</p>
                </div>
                <x-button type="button" variant="accent" size="md" :pill="true" onclick="document.querySelector('.lg\\:sticky')?.scrollIntoView({ behavior: 'smooth' })">
                    Donate Now
                </x-button>
            </div>
        </div>
    @endif

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
