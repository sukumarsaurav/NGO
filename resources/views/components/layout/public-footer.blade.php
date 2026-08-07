@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $orgName = $settings->get('org.name') ?: config('app.name');
    $socials = array_filter([
        'Facebook' => $settings->get('social.facebook'),
        'Instagram' => $settings->get('social.instagram'),
        'Twitter' => $settings->get('social.twitter'),
        'LinkedIn' => $settings->get('social.linkedin'),
        'YouTube' => $settings->get('social.youtube'),
    ]);
    $address = trim(implode(', ', array_filter([
        $settings->get('org.address_line1'),
        $settings->get('org.address_line2'),
        $settings->get('org.city'),
        $settings->get('org.state'),
        $settings->get('org.pincode'),
    ])));
@endphp

<footer class="border-t border-line-divider bg-surface">
    {{-- Newsletter signup — dark-tinted photo band, moved here from the homepage so it
         appears on every page rather than only the homepage. --}}
    <section class="relative overflow-hidden">
        <img
            src="{{ asset('images/hero-community.png') }}"
            alt=""
            aria-hidden="true"
            loading="lazy"
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

    <div class="mx-auto grid max-w-container grid-cols-1 gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4">
        <div>
            <img src="{{ asset('images/branding/logo-horizontal.png') }}" alt="{{ $orgName }}" class="mb-3 h-8 w-auto">
            <p class="sr-only">{{ $orgName }}</p>
            @if ($settings->get('org.tagline'))
                <p class="mb-4 text-sm text-content-muted">{{ $settings->get('org.tagline') }}</p>
            @endif
            @if ($socials !== [])
                <div class="flex gap-3 text-sm text-content-muted">
                    @foreach ($socials as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="hover:text-link">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-content">Explore</p>
            <ul class="space-y-2 text-sm text-content-muted">
                <li><a href="{{ route('campaigns.index') }}" wire:navigate class="hover:text-link">Campaigns</a></li>
                <li><a href="{{ route('campaigns.monthly-giving') }}" wire:navigate class="hover:text-link">Monthly Giving</a></li>
                <li><a href="{{ route('fundraiser.show') }}" wire:navigate class="hover:text-link">Start a Fundraise</a></li>
                <li><a href="{{ route('donate.show') }}" wire:navigate class="hover:text-link">How to Donate</a></li>
            </ul>
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-content">Information</p>
            <ul class="space-y-2 text-sm text-content-muted">
                <li><a href="{{ route('pages.show', 'about') }}" wire:navigate class="hover:text-link">About</a></li>
                <li><a href="{{ route('blog.index') }}" wire:navigate class="hover:text-link">Blog</a></li>
                <li><a href="{{ route('contact.show') }}" wire:navigate class="hover:text-link">Contact</a></li>
                <li><a href="{{ route('gallery.index') }}" wire:navigate class="hover:text-link">Gallery</a></li>
                <li><a href="{{ route('partners.index') }}" wire:navigate class="hover:text-link">Partners</a></li>
                <li><a href="{{ route('certificates.index') }}" wire:navigate class="hover:text-link">Certificates</a></li>
                <li><a href="{{ route('csr-partnership.show') }}" wire:navigate class="hover:text-link">CSR Partnership</a></li>
                <li><a href="{{ route('internship.show') }}" wire:navigate class="hover:text-link">Internship</a></li>
                <li><a href="{{ route('pages.show', 'privacy-policy') }}" wire:navigate class="hover:text-link">Privacy Policy</a></li>
                <li><a href="{{ route('pages.show', 'terms-conditions') }}" wire:navigate class="hover:text-link">Terms &amp; Conditions</a></li>
            </ul>
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-content">Contact</p>
            <ul class="space-y-2 text-sm text-content-muted">
                @if ($address)
                    <li>{{ $address }}</li>
                @endif
                @if ($settings->get('org.phone'))
                    <li>{{ $settings->get('org.phone') }}</li>
                @endif
                @if ($settings->get('org.email'))
                    <li><a href="mailto:{{ $settings->get('org.email') }}" class="hover:text-link">{{ $settings->get('org.email') }}</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-line-divider">
        <div class="mx-auto flex max-w-container flex-col items-center gap-3 px-4 py-6 text-xs text-content-muted sm:flex-row sm:justify-between sm:px-6">
            <div class="flex flex-wrap items-center gap-3">
                <span>UPI</span>
                <span>Visa</span>
                <span>Mastercard</span>
                <span>RuPay</span>
                <span>Net Banking</span>
            </div>
            {{-- Trust-stack element, not boilerplate — a donor verifying an
                 NGO looks for exactly these. See docs/06-UI-UX-FOUNDATION.md §3. --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($settings->get('org.registration_number'))
                    <span>Reg. No: {{ $settings->get('org.registration_number') }}</span>
                @endif
                @if ($settings->get('org.12a_number'))
                    <span>12A: {{ $settings->get('org.12a_number') }}</span>
                @endif
                @if ($settings->get('org.80g_number'))
                    <span>80G: {{ $settings->get('org.80g_number') }}</span>
                @endif
            </div>
            <p>&copy; {{ now()->year }} {{ $orgName }}. All rights reserved.</p>
        </div>
    </div>
</footer>
