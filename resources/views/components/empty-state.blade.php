{{--
    Empty state — docs/08-DESIGN-SYSTEM.md §10.10.

    Icon (muted) + heading + one line of body + optional action. §10.10: "Every list, grid
    and report has one. It is a component, not a per-page improvisation." It was five
    per-page improvisations, all of which used `p-10` — off the spacing scale, so all five
    rendered with no padding at all.

    The action slot is optional because not every empty state has a next step: "no campaigns
    are live right now" is a state the visitor cannot act on, and offering a button that
    does not help is worse than offering none.
--}}
@props([
    'title' => null,
    'icon' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-line-divider bg-surface p-12 text-center']) }}>
    @if ($icon)
        <svg class="mx-auto mb-4 h-8 w-8 text-content-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-4l-2 3h-4l-2-3H4" />
        </svg>
    @endif

    @if ($title)
        <p class="mb-1 text-lg font-semibold text-content">{{ $title }}</p>
    @endif

    <div class="text-content-muted">{{ $slot }}</div>

    @isset($action)
        <div class="mt-6 flex justify-center">{{ $action }}</div>
    @endisset
</div>
