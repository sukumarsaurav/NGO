@props(['title' => null, 'description' => null, 'ogImage' => null, 'noindex' => false, 'titleIsComplete' => false])

@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $orgName = $settings->get('org.name') ?: config('app.name');
    $whatsapp = $settings->get('org.whatsapp');

    // Declared once and rendered twice (header bar + mobile drawer). Previously these
    // five links were duplicated verbatim, so a nav change had to be made in two places
    // or the drawer silently drifted out of sync with the bar.
    $navItems = [
        [
            'label' => 'Explore Campaigns',
            'href' => route('campaigns.index'),
            'active' => request()->routeIs('campaigns.index', 'campaigns.show', 'campaigns.category'),
        ],
        [
            'label' => 'Monthly Giving',
            'href' => route('campaigns.monthly-giving'),
            'active' => request()->routeIs('campaigns.monthly-giving'),
        ],
        [
            'label' => 'Start a Fundraise',
            'href' => route('fundraiser.show'),
            'active' => request()->routeIs('fundraiser.*'),
        ],
        [
            'label' => 'About',
            'href' => route('pages.show', 'about'),
            'active' => request()->routeIs('pages.show') && request()->route('slug') === 'about',
        ],
        [
            'label' => 'Blog',
            'href' => route('blog.index'),
            'active' => request()->routeIs('blog.*'),
        ],
    ];

    // A second, smaller array rather than five more entries in $navItems above —
    // five more flat top-level links would overflow the desktop bar. Rendered as
    // a "More" dropdown on desktop and as extra rows (after a small label) in the
    // mobile drawer, from this one source, for the same reason $navItems is
    // declared once: a nav change made in only one place silently drifts.
    $moreNavItems = [
        [
            'label' => 'Gallery',
            'href' => route('gallery.index'),
            'active' => request()->routeIs('gallery.index'),
        ],
        [
            'label' => 'Partners',
            'href' => route('partners.index'),
            'active' => request()->routeIs('partners.index'),
        ],
        [
            'label' => 'Certificates',
            'href' => route('certificates.index'),
            'active' => request()->routeIs('certificates.index'),
        ],
        [
            'label' => 'CSR Partnership',
            'href' => route('csr-partnership.show'),
            'active' => request()->routeIs('csr-partnership.*'),
        ],
        [
            'label' => 'Internship',
            'href' => route('internship.show'),
            'active' => request()->routeIs('internship.*'),
        ],
    ];
    $moreNavActive = collect($moreNavItems)->contains('active', true);
@endphp

<!DOCTYPE html>
{{-- No `h-full` on <html>/<body>. `height: 100%` pins them to one viewport, which caps the
     containing block of `position: sticky` children — the header would unstick and scroll
     away after 100vh. `min-h-screen` + `flex-col` on <body> keeps the footer at the bottom
     of short pages without capping the height of long ones. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? ($titleIsComplete ? $title : $title.' — '.config('app.name')) : config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta property="og:title" content="{{ $title ?: config('app.name') }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => $orgName,
            'url' => url('/'),
            'email' => $settings->get('org.email') ?: null,
            'telephone' => $settings->get('org.phone') ?: null,
            'sameAs' => array_values(array_filter([
                $settings->get('social.facebook'),
                $settings->get('social.instagram'),
                $settings->get('social.twitter'),
                $settings->get('social.linkedin'),
                $settings->get('social.youtube'),
            ])),
        ]), JSON_UNESCAPED_SLASHES) !!}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- `x-data` lives on <body>, not on a wrapper <div>. A wrapper around the header and the
     drawer is only as tall as the header (the drawer is `fixed`), and a sticky element
     cannot travel outside its parent's box — so the header scrolled away immediately. --}}
<body x-data="{ drawerOpen: false }" class="flex min-h-screen flex-col bg-background font-sans text-content antialiased">
    {{-- First tab stop on every page. Without it a keyboard user re-traverses the logo,
         five nav links, Login and Donate before reaching the content, on every navigation. --}}
    <a
        href="#main"
        class="sr-only rounded-md bg-action px-4 py-3 text-sm font-semibold text-action-on focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-toast"
    >
        Skip to content
    </a>

        {{-- `data-sticky-header` is picked up by initStickyHeaders() in resources/js/app.js,
             which sets `data-stuck` once the header pins. The divider/shadow cross-fade
             that keys off it lives in tokens.css.

             The header does NOT hide on scroll-down. That pattern buys back 64px of
             viewport, but it also takes the Donate button off screen for the whole time
             the donor is reading — on a giving site the persistent CTA is worth more than
             the height. It also does not shrink: the campaign page's section nav pins at
             `top-16` against this height, and an animated header height would leave that
             nav overlapping or floating during the transition. --}}
        <header data-sticky-header class="sticky top-0 z-header h-16 bg-surface">
            {{-- Resting separator and pinned shadow, cross-faded on `opacity` alone. --}}
            <div class="header-divider pointer-events-none absolute inset-x-0 bottom-0 h-px bg-line-divider" aria-hidden="true"></div>
            <div class="header-shadow pointer-events-none absolute inset-0 shadow-md" aria-hidden="true"></div>

            {{-- `gap-3` as well as `justify-between`: with the logo now a full lockup rather
                 than a 32px circle, `justify-between` alone let it butt straight up against
                 the Donate button once the two together filled the bar. --}}
            <div class="relative mx-auto flex h-16 max-w-container items-center justify-between gap-3 px-4 sm:px-6">
                {{-- One image at every breakpoint, not a `<picture>` swapping in the circular
                     mark below `sm`. That mark is not a mark: it is the whole lockup —
                     emblem *and* the "VISION GOOD WORK GLOBAL FOUNDATION" wordmark — set
                     inside a ring, so at the 32px it was rendered at, the organisation's
                     name was an unreadable smudge and the ring ate a further ~20% of the
                     box. Client feedback, 2026-08-14: "logo is not clear in mobile view."
                     There is no space problem to solve here — on a 375px viewport the bar
                     holds only Donate and the hamburger, leaving ~200px unused to the right
                     of the logo, which comfortably fits the horizontal lockup.

                     Dropping `<picture>` also drops a request: the mark was a second 152 KB
                     asset that existed only for this one breakpoint. --}}
                <a href="{{ url('/') }}" wire:navigate class="flex min-w-0 items-center rounded-sm" aria-label="{{ $orgName }} — home">
                    {{-- `width`/`height` are the asset's real intrinsic size, so the box is
                         reserved before the image lands and the header does not reflow.
                         The source was re-cut for this change: the old file carried ~21%
                         transparent padding, which shrank the visible logo inside whatever
                         height we set. Trimmed to its ink, the same CSS height now renders
                         the lockup about a quarter larger.

                         `h-8` is the largest step on the project's spacing scale that still
                         clears the Donate button and the hamburger on a 375px viewport: the
                         trimmed lockup is 6.3:1, so 32px tall is 203px wide, against 215px
                         of free bar once `gap-3` is taken out. It is held at h-8 on desktop
                         too — going up a step to h-12 would be 304px wide and crowd the
                         `lg` nav. `max-w-full object-contain` is the floor below 375px:
                         narrower phones scale the lockup down proportionally rather than
                         letting it slide under the button. --}}
                    <img
                        src="{{ asset('images/branding/logo-horizontal.png') }}"
                        alt="{{ $orgName }}"
                        width="760"
                        height="120"
                        class="h-8 w-auto max-w-full object-contain"
                    >
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium lg:flex" aria-label="Main">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate
                            @if ($item['active']) aria-current="page" @endif
                            class="group relative py-2 transition-colors duration-fast {{ $item['active'] ? 'text-link' : 'text-content hover:text-link' }}"
                        >
                            {{ $item['label'] }}
                            {{-- Underline indicator. Scale rather than width so it stays on the
                                 compositor, and origin-left so it wipes in from the start of the
                                 word instead of growing out of its centre. 2px matches
                                 --focus-ring-width, so indicator and focus ring read as one system. --}}
                            <span
                                aria-hidden="true"
                                class="absolute inset-x-0 bottom-0 h-[2px] origin-left rounded-full bg-action transition-transform duration-fast ease-out {{ $item['active'] ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"
                            ></span>
                        </a>
                    @endforeach

                    {{-- "More" dropdown — same x-show/x-transition idiom as the mobile drawer
                         below, just a smaller/inline instance of it. `@click.outside` closes it
                         without needing a full-screen scrim like the drawer has. --}}
                    <div class="relative" x-data="{ moreOpen: false }" @click.outside="moreOpen = false" @keydown.escape="moreOpen = false">
                        <button
                            type="button"
                            @click="moreOpen = ! moreOpen"
                            class="group relative flex items-center gap-1 py-2 transition-colors duration-fast {{ $moreNavActive ? 'text-link' : 'text-content hover:text-link' }}"
                            :aria-expanded="moreOpen ? 'true' : 'false'"
                            aria-haspopup="true"
                        >
                            More
                            <svg class="h-4 w-4 transition-transform duration-fast" :class="moreOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                            <span
                                aria-hidden="true"
                                class="absolute inset-x-0 bottom-0 h-[2px] origin-left rounded-full bg-action transition-transform duration-fast ease-out {{ $moreNavActive ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}"
                            ></span>
                        </button>

                        <div
                            x-show="moreOpen"
                            x-cloak
                            x-transition:enter="transition duration-fast ease-out"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:leave="transition duration-fast ease-in-out"
                            x-transition:leave-end="opacity-0"
                            class="absolute right-0 top-full z-header mt-2 w-[14rem] rounded-md border border-line-divider bg-surface p-2 shadow-lg"
                        >
                            @foreach ($moreNavItems as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    wire:navigate
                                    @click="moreOpen = false"
                                    @if ($item['active']) aria-current="page" @endif
                                    class="flex min-h-touch items-center rounded-md px-3 py-2 text-sm transition-colors duration-fast {{ $item['active'] ? 'bg-trust text-trust-text' : 'text-content hover:bg-surface-muted' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </nav>

                <div class="flex items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" wire:navigate class="hidden rounded-sm text-sm font-medium text-content transition-colors duration-fast hover:text-link sm:inline">Login</a>
                    @endguest
                    <a
                        href="{{ route('donate.show') }}"
                        wire:navigate
                        class="inline-flex min-h-touch items-center rounded-md bg-action px-4 py-2 text-sm font-semibold text-action-on transition-colors duration-fast hover:bg-action-hover active:bg-action-active"
                    >
                        Donate
                    </a>
                    <button
                        type="button"
                        @click="drawerOpen = true"
                        class="-mr-2 inline-flex min-h-touch min-w-touch items-center justify-center rounded-md p-2 text-content transition-colors duration-fast hover:bg-surface-muted lg:hidden"
                        aria-label="Open menu"
                        aria-controls="mobile-menu"
                        :aria-expanded="drawerOpen ? 'true' : 'false'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Scrim fades; the panel slides. Fading a drawer in place reads as an overlay
             appearing from nowhere, while the slide tells you where it came from and
             therefore where dismissing it will send it. --}}
        <div
            x-show="drawerOpen"
            x-cloak
            x-transition:enter="transition-opacity duration-base ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:leave="transition-opacity duration-fast ease-in-out"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-drawer bg-scrim"
            @click="drawerOpen = false"
            aria-hidden="true"
        ></div>

        {{-- `x-trap.noscroll` does three jobs the previous drawer did none of: it holds
             focus inside the panel while it is open, locks body scroll so the page behind
             does not scroll under the drawer, and returns focus to the hamburger on close. --}}
        <div
            id="mobile-menu"
            x-show="drawerOpen"
            x-cloak
            x-trap.noscroll="drawerOpen"
            @keydown.escape.window="drawerOpen = false"
            x-transition:enter="transition-transform duration-base ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:leave="transition-transform duration-fast ease-in-out"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-drawer flex w-[18rem] flex-col bg-surface shadow-lg"
            role="dialog"
            aria-modal="true"
            aria-label="Menu"
        >
            <div class="flex h-16 items-center justify-between border-b border-line-divider px-6">
                <span class="text-sm font-semibold text-content">Menu</span>
                <button
                    type="button"
                    @click="drawerOpen = false"
                    class="-mr-2 inline-flex min-h-touch min-w-touch items-center justify-center rounded-md p-2 text-content transition-colors duration-fast hover:bg-surface-muted"
                    aria-label="Close menu"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <nav class="flex flex-1 flex-col overflow-y-auto p-3 text-base font-medium" aria-label="Main">
                @foreach ($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        wire:navigate
                        @if ($item['active']) aria-current="page" @endif
                        class="flex min-h-touch items-center rounded-md px-3 py-3 transition-colors duration-fast {{ $item['active'] ? 'bg-trust text-trust-text' : 'text-content hover:bg-surface-muted' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-content-muted">More</p>
                @foreach ($moreNavItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        wire:navigate
                        @if ($item['active']) aria-current="page" @endif
                        class="flex min-h-touch items-center rounded-md px-3 py-3 transition-colors duration-fast {{ $item['active'] ? 'bg-trust text-trust-text' : 'text-content hover:bg-surface-muted' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @guest
                    <a href="{{ route('login') }}" wire:navigate class="flex min-h-touch items-center rounded-md px-3 py-3 text-content transition-colors duration-fast hover:bg-surface-muted">Login</a>
                @endguest
            </nav>

            {{-- The drawer covers the header, and with it the Donate button. Repeating it
                 here keeps the primary action reachable from inside the menu. --}}
            <div class="border-t border-line-divider p-3">
                <a
                    href="{{ route('donate.show') }}"
                    wire:navigate
                    class="flex min-h-touch w-full items-center justify-center rounded-md bg-action px-4 py-3 text-base font-semibold text-action-on transition-colors duration-fast hover:bg-action-hover active:bg-action-active"
                >
                    Donate
                </a>
            </div>
    </div>

    {{-- `tabindex="-1"` makes this a valid target for the skip link: without it the browser
         scrolls but leaves focus at the top of the document. --}}
    <main id="main" tabindex="-1" class="mx-auto flex w-full max-w-container flex-1 flex-col items-center px-4 py-12 focus:outline-none sm:px-6">
        {{ $slot }}
    </main>

    <x-layout.public-footer />

    @if ($whatsapp)
        <a
            href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}"
            target="_blank"
            rel="noopener"
            class="fixed bottom-6 right-6 z-float flex h-16 w-16 items-center justify-center rounded-full bg-success text-2xl text-white shadow-lg"
            aria-label="Chat on WhatsApp"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.39c1.44.78 3.06 1.2 4.76 1.2h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.77 14.08c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.12.11-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.24-4.76-4.14-4.9-4.33-.14-.19-1.18-1.57-1.18-3 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.53.72 1.8.86.27.14.44.2.51.31.07.12.07.68-.17 1.36z"/></svg>
        </a>
    @endif

    @livewireScripts
</body>
</html>
