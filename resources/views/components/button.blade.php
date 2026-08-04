{{--
    Button — docs/08-DESIGN-SYSTEM.md §10.1.

    Renders an <a> when `href` is given, a <button> otherwise, so a link styled as a button
    stops being a hand-written class string. Every public CTA on the site used to be one:
    ten different padding combinations across thirty raw elements, none of them carrying a
    hover, active or transition state.

    `full` rather than a hard-coded `w-full`. The previous version was always full-width,
    which is exactly why almost nothing used it — a button that cannot sit inline is
    unusable in a header or a button row, so every call site rewrote it by hand.

    No `focus-visible:outline-none`. The previous version had it, which silently removed the
    focus ring: tokens.css §8 draws that ring from a `:where(...)` rule, and `:where()`
    contributes zero specificity, so any utility beats it. Buttons had no focus indicator.

    `pill` swaps the default rounded-md corners for rounded-full, same opt-in pattern as
    `full`. Added for the homepage/campaign-card redesign's pill-shaped CTAs — the default
    stays rounded-md so this is additive, not a visual change to any existing call site.

    SIZE / TOUCH TARGET — one deliberate deviation from §10.1, flagged rather than silent:
    the spec lists `base` as 40px while also requiring a 44×44 minimum touch target. 40px
    cannot satisfy that, so `base` is 44px here. `sm` (32px) stays admin-only and must never
    appear on a public touch surface.
--}}
@props([
    'variant' => 'primary',
    'size' => 'base',
    'full' => false,
    'pill' => false,
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold '
        .'transition-colors duration-fast disabled:cursor-not-allowed disabled:opacity-60';

    $sizes = [
        'sm' => 'h-8 px-4 text-sm',                // 32px — admin only, below the touch minimum
        'base' => 'min-h-touch px-4 py-2 text-base',
        'lg' => 'min-h-touch px-4 py-3 text-base',
        'xl' => 'min-h-touch px-6 py-3 text-lg',   // donate CTA only
    ];

    // §10.1's `secondary` is brand-50/brand-700. Those are the same measured values the
    // semantic trust pair already carries (10.08:1), so this uses the semantic token
    // rather than reaching into a primitive ramp — which §13 calls a review finding.
    $variants = [
        'primary' => 'bg-action text-action-on hover:bg-action-hover active:bg-action-active',
        'secondary' => 'bg-trust text-trust-text hover:bg-brand-200',
        'tertiary' => 'text-link hover:bg-surface-muted hover:text-link-hover',
        'accent' => 'bg-highlight text-highlight-on hover:bg-accent-400',
        'danger' => 'bg-danger text-action-on hover:bg-danger-text',
    ];

    $classes = trim($base.' '.($pill ? 'rounded-full' : 'rounded-md').' '.($sizes[$size] ?? $sizes['base']).' '.($variants[$variant] ?? $variants['primary'])
        .($full ? ' w-full' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
