{{--
    Alert — docs/08-DESIGN-SYSTEM.md §10.9.

    Tinted surface, 4px left border in the status colour, icon, body text. The icon is not
    decoration: §10.9 requires each variant to carry an icon *and* a text label so the
    variant never reads by colour alone. Icons are inlined rather than pulled from an icon
    set because the project has no icon component yet (§9 is unimplemented) and four paths
    do not justify one.

    Replaces roughly ten hand-rolled banners, every one of which had picked its own colour
    pair — and most of which picked classes that did not exist, so they rendered as plain
    body text on no background. See docs/10-UI-UX-AUDIT.md §1.3 and §1.4.

    `role`: danger and warning are assertive because they report something that blocks the
    user (a failed payment, a price change mid-checkout). info and success are polite.
--}}
@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variants = [
        'info' => ['class' => 'border-info bg-info-bg text-info-text', 'role' => 'status'],
        'success' => ['class' => 'border-success bg-success-bg text-success-text', 'role' => 'status'],
        'warning' => ['class' => 'border-warning bg-warning-bg text-warning-text', 'role' => 'alert'],
        'danger' => ['class' => 'border-danger bg-danger-bg text-danger-text', 'role' => 'alert'],
    ];

    $config = $variants[$variant] ?? $variants['info'];

    $icons = [
        'info' => 'M11 9h2V7h-2m1 13c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8m0-18A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2m-1 15h2v-6h-2v6Z',
        'success' => 'M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2m-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9Z',
        'warning' => 'M13 14h-2V9h2m0 9h-2v-2h2M1 21h22L12 2 1 21Z',
        'danger' => 'M11 15h2v2h-2v-2m0-8h2v6h-2V7m1-5A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2Z',
    ];

    // The variant name is the text label §10.9 asks for on the icon itself, so the alert
    // never depends on hue to say which kind it is.
    $labels = ['info' => 'Information', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Error'];
@endphp

<div
    role="{{ $config['role'] }}"
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-md border-l-4 p-3 text-sm '.$config['class']]) }}
>
    <svg class="mt-1 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" role="img" aria-label="{{ $labels[$variant] ?? 'Information' }}">
        <path d="{{ $icons[$variant] ?? $icons['info'] }}" />
    </svg>

    <div class="min-w-0">
        @if ($title)
            <p class="mb-1 font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
</div>
