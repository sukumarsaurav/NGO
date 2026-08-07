{{--
    Flash message — `session('status')` rendered with an actual entrance transition instead
    of appearing fully-formed the instant the page paints. Identical markup was repeated on
    csr-partnership/show, internship/show and contact/show (a fourth call site is the trigger
    for extraction per this codebase's own convention — see the comment on home.blade.php's
    `$causeIcon`) — extracted here rather than fixing the same copy-paste three times. See
    docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §9.

    The footer's newsletter-subscribe status message is NOT this component — it sits on a
    dark photo band with its own colour treatment (`text-accent-300`, no background), enough
    of a visual departure that sharing this component would mean fighting its defaults rather
    than reusing them.
--}}
@if (session('status'))
    <div
        x-data="{ shown: false }"
        x-init="$nextTick(() => shown = true)"
        x-show="shown"
        x-cloak
        x-transition:enter="transition duration-base ease-out"
        x-transition:enter-start="opacity-0 -translate-y-1"
        {{ $attributes->merge(['class' => 'mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text']) }}
    >
        {{ session('status') }}
    </div>
@endif
