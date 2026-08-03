@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $portalUnreadNoticeCount = auth()->check()
        ? \App\Models\NoticeRecipient::query()->where('user_id', auth()->id())->whereNull('read_at')->count()
        : 0;

    $portalNav = [
        ['label' => 'Dashboard', 'href' => route('portal.dashboard'), 'active' => request()->routeIs('portal.dashboard')],
        ['label' => 'Profile', 'href' => route('portal.profile.edit'), 'active' => request()->routeIs('portal.profile.*')],
        ['label' => 'Documents', 'href' => route('portal.documents.index'), 'active' => request()->routeIs('portal.documents.*')],
        ['label' => 'Donations', 'href' => route('portal.donations.index'), 'active' => request()->routeIs('portal.donations.*')],
        ['label' => 'Monthly giving', 'href' => route('portal.subscriptions.index'), 'active' => request()->routeIs('portal.subscriptions.*')],
        ['label' => 'Notices', 'href' => route('portal.notices.index'), 'active' => request()->routeIs('portal.notices.*'), 'badge' => $portalUnreadNoticeCount],
    ];
@endphp

<body class="min-h-screen bg-background font-sans text-content antialiased">
    <a
        href="#main"
        class="sr-only rounded-md bg-action px-4 py-3 text-sm font-semibold text-action-on focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-toast"
    >
        Skip to content
    </a>

    {{-- Sticky for the same reason as the public header, and with the same divider/shadow
         cross-fade — the portal's donation and document tables are long, and the nav is the
         only way back out of them. --}}
    <header data-sticky-header class="sticky top-0 z-header bg-surface">
        <div class="header-divider pointer-events-none absolute inset-x-0 bottom-0 h-px bg-line-divider" aria-hidden="true"></div>
        <div class="header-shadow pointer-events-none absolute inset-0 shadow-md" aria-hidden="true"></div>

        <div class="relative mx-auto flex h-16 max-w-container items-center gap-4 px-4 sm:px-6">
            <a href="{{ route('portal.dashboard') }}" class="flex shrink-0 items-center rounded-sm" aria-label="{{ config('app.name') }} — dashboard">
                {{-- Portal header is a tighter utility bar than the public site's,
                     alongside a nav with six links — the compact mark fits it better
                     than the full horizontal lockup would at every width. --}}
                <img src="{{ asset('images/branding/logo-mark.png') }}" alt="{{ config('app.name') }}" width="32" height="32" class="h-8 w-8">
            </a>

            {{-- Six links plus Log out will not fit 375px. A horizontally scrollable rail
                 beats a drawer for a utility bar this shallow: every destination stays one
                 tap away instead of two, and it needs no JavaScript — this layout does not
                 load Livewire, so Alpine is not available here. `.scroll-rail` (tokens.css)
                 hides the scrollbar and fades the cut-off edges; revealCurrentNavItem() in
                 app.js scrolls the current page's item into view on load. --}}
            <nav class="scroll-rail flex min-w-0 flex-1 items-center gap-1 text-sm" aria-label="Portal">
                @foreach ($portalNav as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @if ($item['active']) aria-current="page" @endif
                        class="flex min-h-touch shrink-0 items-center whitespace-nowrap rounded-md px-3 transition-colors duration-fast {{ $item['active'] ? 'bg-trust font-semibold text-trust-text' : 'text-content-muted hover:bg-surface-muted hover:text-content' }}"
                    >
                        {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="ml-2 rounded-full bg-action px-2 py-1 text-xs font-semibold text-action-on">
                                {{ $item['badge'] }}
                                <span class="sr-only">unread notices</span>
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button
                    type="submit"
                    class="flex min-h-touch items-center whitespace-nowrap rounded-md px-3 text-sm text-content-muted transition-colors duration-fast hover:bg-surface-muted hover:text-content"
                >
                    Log out
                </button>
            </form>
        </div>
    </header>

    <main id="main" tabindex="-1" class="mx-auto max-w-container px-4 py-8 focus:outline-none sm:px-6">
        {{ $slot }}
    </main>
</body>
</html>
