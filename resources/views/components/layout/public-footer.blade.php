@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $orgName = $settings->get('org.name') ?: config('app.name');
    $address = trim(implode(', ', array_filter([
        $settings->get('org.address_line1'),
        $settings->get('org.address_line2'),
        $settings->get('org.city'),
        $settings->get('org.state'),
        $settings->get('org.pincode'),
    ])));

    // Three balanced groups, declared as data and rendered by one loop below.
    //
    // Previously two columns: "Explore" with four links and "Information" with ten, which
    // on desktop left one column running far past the other three, and on mobile — where
    // every column is stacked full-width, one link per row — produced a ten-row wall of
    // text. Splitting the long column in two and moving the legal pages to the bottom bar
    // (where visitors expect them) gives four columns of four. No link was dropped.
    $linkGroups = [
        'Explore' => [
            ['label' => 'Campaigns', 'href' => route('campaigns.index')],
            ['label' => 'Monthly Giving', 'href' => route('campaigns.monthly-giving')],
            ['label' => 'Start a Fundraise', 'href' => route('fundraiser.show')],
            ['label' => 'How to Donate', 'href' => route('donate.show')],
        ],
        'About Us' => [
            ['label' => 'About', 'href' => route('pages.show', 'about')],
            ['label' => 'Blog', 'href' => route('blog.index')],
            ['label' => 'Gallery', 'href' => route('gallery.index')],
            ['label' => 'Certificates', 'href' => route('certificates.index')],
        ],
        // The contact page is deliberately not listed here: it sits in the Contact column
        // instead, as "Send us a message". A "Contact" link directly beside a column
        // headed "Contact" read as a duplicate of it rather than as the form it opens.
        'Get Involved' => [
            ['label' => 'CSR Partnership', 'href' => route('csr-partnership.show')],
            ['label' => 'Internship', 'href' => route('internship.show')],
            ['label' => 'Partners', 'href' => route('partners.index')],
        ],
    ];

    $payments = ['UPI', 'Visa', 'Mastercard', 'RuPay', 'Net Banking'];

    $credentials = array_filter([
        $settings->get('org.registration_number') ? 'Reg. No: '.$settings->get('org.registration_number') : null,
        $settings->get('org.12a_number') ? '12A: '.$settings->get('org.12a_number') : null,
        $settings->get('org.80g_number') ? '80G: '.$settings->get('org.80g_number') : null,
    ]);
@endphp

<footer class="border-t border-line-divider bg-surface">
    {{-- Newsletter signup — dark-tinted photo band, moved here from the homepage so it
         appears on every page rather than only the homepage. --}}
    <section class="relative overflow-hidden">
        {{-- `.jpg`, and 107 KB rather than 792 KB. The old file was a JPEG misnamed `.png`,
             stored as a 1024×1024 square, and shipped on every page of the site — while
             this band is short and wide, so `object-cover` cropped most of that square away
             before anyone saw it, and the 80% overlay below hides the rest of the detail.
             Re-cut to the 16:9 slice that is actually visible. --}}
        <img
            src="{{ asset('images/hero-community.jpg') }}"
            alt=""
            aria-hidden="true"
            loading="lazy"
            decoding="async"
            width="1280"
            height="720"
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-brand-900/80"></div>

        <div class="relative mx-auto max-w-md px-6 py-12 text-center sm:px-8 sm:py-16">
            <h2 class="mb-2 font-heading text-xl font-bold text-white sm:text-2xl">Stay in the loop</h2>
            <p class="mb-6 text-sm text-brand-200">Get updates on campaigns and the impact your support makes.</p>

            {{-- Entrance transition instead of appearing fully-formed — see
                 docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §0/§9. Not `<x-flash-message>`:
                 that component's default styling (tinted box, dark text) is built for a
                 light card, not this dark photo band. --}}
            @if (session('status'))
                <p
                    x-data="{ shown: false }"
                    x-init="$nextTick(() => shown = true)"
                    x-show="shown"
                    x-cloak
                    x-transition:enter="transition duration-base ease-out"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    class="mb-3 text-sm text-accent-300"
                >{{ session('status') }}</p>
            @endif
            {{-- Two inline `style=` attributes used to bypass the token system entirely here
                 (a raw `var(--accent-400)`/`var(--brand-900)` button and a raw
                 `rgba(255,255,255,0.15)` input background) instead of using `<x-button>` and
                 a utility class. See docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §2.3. --}}
            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex flex-col gap-3 sm:flex-row">
                @csrf
                <label for="footer-newsletter-email" class="sr-only">Email address</label>
                <input type="email" id="footer-newsletter-email" name="email" required placeholder="you@example.com" class="min-h-touch flex-1 rounded-sm border-0 bg-white/15 px-4 py-3 text-base text-white placeholder-white/60">
                <x-button variant="accent">Subscribe</x-button>
            </form>
            @error('email') <p class="mt-3 text-xs" style="color: #fca5a5;">{{ $message }}</p> @enderror
        </div>
    </section>

    {{-- Two columns from the smallest screen up, not one. Stacking every column full-width
         on mobile turned fourteen links into fourteen rows and made the footer 1200px tall
         — roughly a screen and a half of scrolling past the end of the page. Pairing the
         columns halves that at no cost: the labels are short enough to sit in ~160px.

         The 12-column desktop track exists so the brand block can take 4 and the four
         content columns 2 each; a plain `lg:grid-cols-4` would have forced the brand block
         to the same width as a list of four short links. --}}
    <div class="mx-auto grid max-w-container grid-cols-2 gap-x-6 gap-y-8 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:gap-x-8">
        <div class="col-span-2 lg:col-span-4">
            {{-- Same re-cut asset as the header, so the same CSS height renders it larger
                 than before; `width`/`height` reserve the box against reflow. --}}
            <img
                src="{{ asset('images/branding/logo-horizontal.png') }}"
                alt="{{ $orgName }}"
                width="760"
                height="120"
                loading="lazy"
                decoding="async"
                class="mb-4 h-8 w-auto max-w-full object-contain"
            >
            @if ($settings->get('org.tagline'))
                <p class="max-w-sm text-sm text-content-muted">{{ $settings->get('org.tagline') }}</p>
            @endif
            {{-- Socials moved to the Contact column below (client review, 2026-08-08) and
                 switched from word-links to icons — see <x-social-links>. --}}
        </div>

        @foreach ($linkGroups as $heading => $links)
            <div class="lg:col-span-2">
                <p class="mb-2 text-sm font-semibold text-content">{{ $heading }}</p>
                {{-- `space-y-1` on the list plus `py-1` on each link, rather than `space-y-2`
                     on bare inline anchors. Same overall column height, but the tap target
                     is the full row height instead of the ~17px of glyph — footer links on a
                     phone were previously a precision-tapping exercise. --}}
                <ul class="space-y-1 text-sm text-content-muted">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}" wire:navigate class="block py-1 transition-colors duration-fast hover:text-link">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="lg:col-span-2">
            <p class="mb-2 text-sm font-semibold text-content">Contact</p>
            {{-- `break-words`: the organisation's address is 40+ characters with no spaces,
                 and this column is ~160px wide on a phone now that the grid is paired. --}}
            <ul class="space-y-1 break-words text-sm text-content-muted">
                @if ($address)
                    <li class="py-1">{{ $address }}</li>
                @endif
                @if ($settings->get('org.phone'))
                    <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $settings->get('org.phone')) }}" class="block py-1 transition-colors duration-fast hover:text-link">{{ $settings->get('org.phone') }}</a></li>
                @endif
                @if ($settings->get('org.email'))
                    <li><a href="mailto:{{ $settings->get('org.email') }}" class="block py-1 transition-colors duration-fast hover:text-link">{{ $settings->get('org.email') }}</a></li>
                @endif
                <li><a href="{{ route('contact.show') }}" wire:navigate class="block py-1 transition-colors duration-fast hover:text-link">Send us a message</a></li>
            </ul>

            <x-social-links class="mt-4" />
        </div>
    </div>

    <div class="border-t border-line-divider">
        <div class="mx-auto max-w-container px-4 py-6 sm:px-6">
            {{-- Payment marks and registration numbers each get a label. Unlabelled, they
                 were five and three loose words sharing a row with the copyright line, which
                 on a phone centre-stacked into a pile of stray text reading
                 "UPI Visa Mastercard RuPay Net Banking". They are trust signals — a donor
                 verifying an NGO looks for exactly these, see docs/06-UI-UX-FOUNDATION.md §3
                 — so they are worth setting as deliberate content rather than debris. --}}
            <div class="flex flex-col gap-6 sm:flex-row sm:justify-between">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-content-muted">We accept</p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($payments as $payment)
                            <li class="rounded-sm border border-line-divider px-2 py-1 text-xs text-content-muted">{{ $payment }}</li>
                        @endforeach
                    </ul>
                </div>

                @if ($credentials !== [])
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-content-muted sm:text-right">Registered</p>
                        <ul class="flex flex-wrap gap-2 sm:justify-end">
                            @foreach ($credentials as $credential)
                                <li class="rounded-sm border border-line-divider px-2 py-1 text-xs text-content-muted">{{ $credential }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Privacy and Terms live here, the first place a visitor looks for them, rather
                 than as rows 9 and 10 of the old "Information" column. --}}
            <div class="mt-6 flex flex-col gap-3 border-t border-line-divider pt-4 text-xs text-content-muted sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ now()->year }} {{ $orgName }}. All rights reserved.</p>
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-1">
                    <li><a href="{{ route('pages.show', 'privacy-policy') }}" wire:navigate class="block py-1 transition-colors duration-fast hover:text-link">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms-conditions') }}" wire:navigate class="block py-1 transition-colors duration-fast hover:text-link">Terms &amp; Conditions</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
