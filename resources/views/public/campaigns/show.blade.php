@php
    $metaTitle = $campaign->meta_title ?: "{$campaign->title} — Donate Now | ".config('app.name');
    $metaDescription = $campaign->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($campaign->story), 155, '');
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $coverUrl = $campaign->cover_image_path ? \Illuminate\Support\Facades\Storage::url($campaign->cover_image_path) : null;

    // Updates and Donors used to render unconditionally — a heading, a shrug, and
    // ~200px of visible emptiness between Story and FAQ, with the section nav still
    // inviting a click into nothing. Each section below is gated on the same boolean
    // used here, so the nav and the section it points at can never disagree. See
    // docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §3.6.
    $hasUpdates = $campaign->updates->isNotEmpty();
    $hasDonors = $campaign->donor_count > 0;
    $sectionNav = collect([
        $campaign->products->isNotEmpty() ? ['id' => 'products', 'label' => 'Products'] : null,
        ['id' => 'story', 'label' => 'Story'],
        $hasUpdates ? ['id' => 'updates', 'label' => 'Updates'] : null,
        $hasDonors ? ['id' => 'donors', 'label' => 'Donors'] : null,
        $faqs->isNotEmpty() ? ['id' => 'faq', 'label' => 'FAQ'] : null,
    ])->filter()->values();
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :og-image="$coverUrl" :noindex="! $campaign->status->isPubliclyVisible()" :title-is-complete="true">
    {{-- `pb-24 lg:pb-0` — clears the 69px fixed mobile donate bar below `lg`, which
         otherwise covers the last section of content and the top of the footer once the
         page is scrolled to the bottom. See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §6. --}}
    <div class="w-full max-w-6xl pb-24 lg:pb-0">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-content-muted">
            <a href="{{ url('/') }}" wire:navigate class="hover:text-link">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('campaigns.category', $campaign->category->slug) }}" wire:navigate class="hover:text-link">{{ $campaign->category->name }}</a>
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
             the right-hand column spanning both rows.

             `lg:grid-cols-12` at a 7/5 split (content/sidebar), not the previous 3-column
             2/1 (66%/33%) split — the reference's own measured proportions are 12 columns
             at 7/5 (~58%/42%), which is *why* its 4 preset pills fit one row comfortably
             where ours needed a 2×2 grid at the narrower width. See
             docs/13-CAMPAIGN-DETAIL-DESIGN-AUDIT-VS-REFERENCE.md PR G. --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <div class="lg:col-span-7 lg:col-start-1 lg:row-start-1">
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
                        {{-- `border-emerald-300`/`text-emerald-800` were dead classes; the
                             `trust` variant already supplies the same measured pair
                             (brand-100/brand-800, 10.08:1) — see
                             docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.2/§2.5. The icon path is
                             a stroke glyph; it was being filled solid (`fill="currentColor"`
                             on a path meant to be stroked), which painted a green blob
                             instead of a check-in-circle. --}}
                        <x-badge variant="trust" class="shadow-sm bg-surface/90 backdrop-blur-md">
                            <svg class="h-3 w-3 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                    {{-- `text-emerald-700` was dead — this rendered as plain muted body text,
                         identical to everything around it, losing the "verified" signal
                         entirely. `text-success-text` is the measured token. --}}
                    <span class="inline-flex items-center gap-1 text-success-text font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Legal Audit Cleared
                    </span>
                </div>
            </div>

            <div class="lg:col-span-5 lg:col-start-8 lg:row-start-1 lg:row-span-2">
                {{-- `top-24` clears the 64px header with room to spare. `max-h` + internal
                     scroll so the sidebar can always fit and stick — the unbounded card
                     measured 839.8px, taller than the ~704px usable height below the header
                     on a 1366×768 laptop, so it never actually stuck there. See
                     docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §2.8. --}}
                {{-- `id="donate"` — the anchor the campaign card's CTA (§2.4) and the mobile
                     sticky bar above link to. `tokens.css`'s global `[id]` rule already gives
                     every element a `scroll-margin-top` clearing the header, so landing here
                     needs no extra offset handling. --}}
                <div id="donate" class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
                    <x-campaigns.donation-card :campaign="$campaign" :percent="$percent" />
                </div>
            </div>

            <div class="lg:col-span-7 lg:col-start-1 lg:row-start-2">
                {{-- `top-16` clears the 64px sticky header; `z-sticky-nav` (100) is the
                     layer allocated to exactly this element in 08-DESIGN-SYSTEM §6. It sits
                     below `z-header` (200) deliberately — the header wins the overlap.

                     Built from `$sectionNav` (below) rather than five hard-coded `<a>`s, so
                     an empty Updates/Donors section drops its nav entry along with the
                     section itself instead of inviting a click into nothing. See
                     docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §3.6. --}}
                {{-- Scrollspy — same underline-wipe treatment as the header nav (§ above),
                     tracking the section currently near the top of the viewport instead of
                     the current page. See docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §3. --}}
                <nav
                    x-data="scrollspyNav({{ $sectionNav->pluck('id')->toJson() }})"
                    class="sticky top-16 z-sticky-nav mb-6 flex flex-wrap gap-6 border-b border-line-divider bg-background py-2 text-sm font-medium text-content-muted"
                    aria-label="Campaign sections"
                >
                    @foreach ($sectionNav as $item)
                        <a
                            href="#{{ $item['id'] }}"
                            :aria-current="active === '{{ $item['id'] }}' ? 'true' : null"
                            class="group relative py-1 transition-colors duration-fast"
                            :class="active === '{{ $item['id'] }}' ? 'text-content' : 'hover:text-content'"
                        >
                            {{ $item['label'] }}
                            <span
                                aria-hidden="true"
                                class="absolute inset-x-0 -bottom-2 h-[2px] origin-left rounded-full bg-action transition-transform duration-fast ease-out"
                                :class="active === '{{ $item['id'] }}' ? 'scale-x-100' : 'scale-x-0'"
                            ></span>
                        </a>
                    @endforeach
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

                @php
                    $orgSettings = app(\App\Services\Settings\SettingsRepository::class);
                @endphp
                <section class="mb-8 rounded-lg border border-line-divider bg-surface p-4" x-data="{ showNgoModal: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-content-muted">NGO Audit & Trust Credentials</p>
                            <p class="text-sm font-medium text-content">Verified Non-Profit Organization</p>
                        </div>
                        {{-- `h-3.5 w-3.5` was dead (spacing scale has no `3.5`) — the SVG fell
                             back to its intrinsic size and rendered at 95×95px, forcing this
                             single line of link text onto three lines beside a giant arrow.
                             See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.4. --}}
                        <button type="button" @click="showNgoModal = true" class="text-xs font-bold text-brand-700 hover:text-brand-900 underline flex items-center gap-1">
                            View Credentials & Certificates
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
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

                    {{-- `<x-modal>` — was a bespoke dialog at `z-50`, *below* the header's
                         `z-header` (200), so the header rendered crisp on top of the "modal"
                         scrim and its Donate button was clickable through it. It also had no
                         focus trap and no body-scroll lock. See
                         docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.3.

                         Values below are read from `org.*` settings, not hard-coded — this
                         used to render literal placeholder identifiers
                         (`AACTV1234F20231`, `IN/2023/0349210`) as if they were the real,
                         verified registration numbers. See §5 of the same audit. Any value
                         still unset renders "Not yet provided" rather than a fabricated
                         number. --}}
                    <x-modal name="showNgoModal" title="NGO Legal Verification">
                        <div class="space-y-3 text-sm text-content">
                            <div class="flex justify-between py-1 border-b border-line-divider/50">
                                <span class="text-content-muted">Organization Name</span>
                                <span class="font-semibold">{{ $orgSettings->get('org.name') ?: config('app.name') }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-line-divider/50">
                                <span class="text-content-muted">80G Tax Exemption No</span>
                                <span class="font-mono text-xs bg-success-bg text-success-text px-2 py-1 rounded">{{ $orgSettings->get('org.80g_number') ?: 'Not yet provided' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-line-divider/50">
                                <span class="text-content-muted">12A Legal Registration</span>
                                <span class="font-mono text-xs bg-success-bg text-success-text px-2 py-1 rounded">{{ $orgSettings->get('org.12a_number') ?: 'Not yet provided' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-line-divider/50">
                                <span class="text-content-muted">NITI Aayog Darpan ID</span>
                                <span class="font-mono text-xs bg-surface-muted px-2 py-1 rounded">{{ $orgSettings->get('org.darpan_id') ?: 'Not yet provided' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-line-divider/50">
                                <span class="text-content-muted">Audit Standard</span>
                                <span class="font-semibold text-success-text">{{ $orgSettings->get('org.audit_standard') ?: 'Not yet provided' }}</span>
                            </div>
                        </div>
                        <div class="pt-4 text-center">
                            {{-- `secondary`, not `accent` — this dismisses the modal, it does
                                 not advance the donation. The loudest control in a dialog
                                 whose only content is reassurance should not be the same
                                 weight as "Donate Now". --}}
                            <x-button type="button" variant="secondary" size="md" full @click="showNgoModal = false">Close & Continue Donation</x-button>
                        </div>
                    </x-modal>
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

                {{-- Framed as a real card (border, shadow, generous padding), not a bare
                     heading over bare text — the reference wraps its story in exactly this
                     kind of surface. `prose` moves onto the inner div since the outer
                     `<section>` now needs its own border/padding classes without the
                     typography plugin's own spacing fighting them. See
                     docs/13-CAMPAIGN-DETAIL-DESIGN-AUDIT-VS-REFERENCE.md PR E. --}}
                <section id="story" class="scroll-mt-[6.5rem] mb-12 rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-12">
                    <h2 class="mb-3 font-heading text-xl font-bold text-content">Story</h2>
                    <div class="prose prose-sm max-w-none text-content">
                        {{-- The story is edited via Filament's RichEditor and stores
                             real HTML — rendered raw here, not escaped, or every
                             paragraph and bold word would show as literal markup.
                             Sanitized regardless of the trusted-admin source —
                             see the same note on portal/notices/show.blade.php. --}}
                        {!! str($campaign->story)->sanitizeHtml() !!}
                    </div>
                </section>

                {{-- Moved out of the sticky sidebar — see the comment on that div above
                     (§2.8) and docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §2.8. Sharing is a
                     plausible next action once someone has read the story, not a payment-flow
                     control competing for space with the amount and the donate button. --}}
                <section class="mb-12">
                    <x-campaigns.share-block :campaign="$campaign" />
                </section>

                {{-- Suppressed when empty, matching Products above — an empty Updates
                     section was ~100px of heading-plus-shrug with a live nav link pointing
                     at it. See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §3.6. --}}
                @if ($hasUpdates)
                    <section id="updates" class="scroll-mt-[6.5rem] mb-12">
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Updates</h2>
                        @foreach ($campaign->updates as $update)
                            <article class="mb-4 border-l-2 border-action pl-4">
                                <p class="text-xs text-content-muted">{{ $update->published_at->format('d M Y') }}</p>
                                <h3 class="font-semibold text-content">{{ $update->title }}</h3>
                                <p class="text-sm text-content-muted">{{ \Illuminate\Support\Str::limit($update->body, 300) }}</p>
                            </article>
                        @endforeach
                    </section>
                @endif

                {{-- Suppressed when the campaign has no donors at all — same reasoning as
                     Updates above. When it has donors, the wall below distinguishes "no
                     donations behind this count" (a data bug — see §2.2) from "the donors
                     behind this count chose to stay anonymous" (expected and correct),
                     rather than showing the same "Be the first to donate." copy either way,
                     directly under a heading that says otherwise. --}}
                @if ($hasDonors)
                    <section id="donors" class="scroll-mt-[6.5rem] mb-12" x-data="{ tab: 'recent' }">
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Donors ({{ $campaign->donor_count }})</h2>
                        {{-- `role="tablist"`/`role="tab"`/`aria-selected`/`aria-controls` —
                             these were plain buttons with no relationship to the panels they
                             switch. See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §6. --}}
                        <div class="mb-3 flex gap-2 text-sm" role="tablist" aria-label="Sort donors">
                            <button type="button" role="tab" id="donors-tab-recent" aria-controls="donors-panel-recent" :aria-selected="(tab === 'recent').toString()" @click="tab = 'recent'" :class="tab === 'recent' ? 'bg-action text-action-on' : 'bg-surface text-content'" class="rounded-full px-3 py-1 font-medium">Recent</button>
                            <button type="button" role="tab" id="donors-tab-generous" aria-controls="donors-panel-generous" :aria-selected="(tab === 'generous').toString()" @click="tab = 'generous'" :class="tab === 'generous' ? 'bg-action text-action-on' : 'bg-surface text-content'" class="rounded-full px-3 py-1 font-medium">Most Generous</button>
                        </div>

                        {{-- `x-transition` cross-fade — these swapped with a hard cut before,
                             despite `x-show` already being in place; only the transition
                             directives were missing. See
                             docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §3. --}}
                        <ul
                            id="donors-panel-recent" role="tabpanel" aria-labelledby="donors-tab-recent"
                            x-show="tab === 'recent'"
                            x-transition:enter="transition duration-fast ease-out" x-transition:enter-start="opacity-0"
                            x-transition:leave="transition duration-fast ease-in-out" x-transition:leave-end="opacity-0"
                            class="divide-y divide-line-divider rounded-lg border border-line-divider"
                        >
                            @forelse ($recentDonors as $donor)
                                <li class="flex items-center justify-between px-4 py-2 text-sm">
                                    <span class="text-content">{{ $donor['name'] }}</span>
                                    <span class="font-semibold text-content">₹{{ number_format($donor['amount'] / 100) }}</span>
                                </li>
                            @empty
                                <li class="px-4 py-4 text-sm text-content-muted">All donors to this campaign have chosen to stay private.</li>
                            @endforelse
                        </ul>
                        <ul
                            id="donors-panel-generous" role="tabpanel" aria-labelledby="donors-tab-generous"
                            x-show="tab === 'generous'"
                            x-transition:enter="transition duration-fast ease-out" x-transition:enter-start="opacity-0"
                            x-transition:leave="transition duration-fast ease-in-out" x-transition:leave-end="opacity-0"
                            x-cloak class="divide-y divide-line-divider rounded-lg border border-line-divider">
                            @forelse ($topDonors as $donor)
                                <li class="flex items-center justify-between px-4 py-2 text-sm">
                                    <span class="text-content">{{ $donor['name'] }}</span>
                                    <span class="font-semibold text-content">₹{{ number_format($donor['amount'] / 100) }}</span>
                                </li>
                            @empty
                                <li class="px-4 py-4 text-sm text-content-muted">All donors to this campaign have chosen to stay private.</li>
                            @endforelse
                        </ul>
                    </section>
                @endif

                @if ($faqs->isNotEmpty())
                    <section id="faq" class="scroll-mt-[6.5rem] mb-12" x-data="{ open: null }">
                        <h2 class="mb-4 font-heading text-xl font-bold text-content">Frequently asked questions</h2>
                        <div class="divide-y divide-line-divider rounded-lg border border-line-divider">
                            {{-- `aria-expanded`/`aria-controls` were absent, and the `±` was
                                 a plain text glyph with no `aria-hidden` — a screen reader had
                                 no way to know these buttons expand anything, or which panel
                                 belongs to which. See
                                 docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §6. --}}
                            @foreach ($faqs as $index => $faq)
                                <div>
                                    <button
                                        type="button"
                                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                                        :aria-expanded="(open === {{ $index }}).toString()"
                                        aria-controls="faq-panel-{{ $index }}"
                                        class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-content"
                                    >
                                        {{ $faq->question }}
                                        <span x-text="open === {{ $index }} ? '−' : '+'" aria-hidden="true"></span>
                                    </button>
                                    {{-- `x-collapse` animates height instead of `x-show`'s hard
                                         `display:none` cut — an FAQ that snaps open read as
                                         noticeably dated next to the rest of this page. See
                                         docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §3. --}}
                                    <div id="faq-panel-{{ $index }}" x-show="open === {{ $index }}" x-collapse x-cloak class="px-4 pb-3 text-sm text-content-muted">
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
                                <x-campaigns.card :campaign="$related" :reveal="$loop->index" class="h-full" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    {{-- Mobile Sticky Bottom Action Bar. `z-donate-bar` (300, tokens.css) replaces a raw
         `z-40` — see docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.3. `primary` (green), not
         `accent` (amber) — this is the donate action itself, per §2.3. Scrolls to `#donate`
         directly rather than an untargeted `.lg\:sticky` query. --}}
    @if ($campaign->status->acceptsDonations())
        {{-- One-time slide-in on arrival instead of appearing fully-formed on first paint —
             see docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §3. `x-cloak` covers the gap
             before Alpine hydrates; the short `setTimeout` (rather than firing on mount)
             gives the entrance a beat to actually read as a slide rather than a flash. --}}
        <div
            x-data="{ shown: false }"
            x-init="setTimeout(() => shown = true, 100)"
            x-show="shown"
            x-cloak
            x-transition:enter="transition-transform duration-base ease-out"
            x-transition:enter-start="translate-y-full"
            class="fixed bottom-0 inset-x-0 z-donate-bar lg:hidden border-t border-line-divider bg-surface/95 backdrop-blur-md p-3 shadow-lg">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-content">{{ $campaign->title }}</p>
                    <p class="text-xs font-bold text-brand-700">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }} raised &middot; {{ $percent }}% funded</p>
                </div>
                <x-button type="button" variant="primary" size="md" :pill="true" onclick="document.getElementById('donate')?.scrollIntoView({ behavior: 'smooth' })">
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
