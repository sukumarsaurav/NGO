{{--
    Badge — status pills and trust labels.

    Covers both the status pills the portal uses (donation / subscription / document /
    charge status) and the trust labels of docs/08-DESIGN-SYSTEM.md §10.5, which are
    structurally the same element: `radius-full`, `space-2` padding-x, `text-xs`.

    Per §10.5 these are labels, not controls — never clickable, never with hover states.

    Every colour pair below is a measured semantic token. The pills these replace used raw
    Tailwind palette names (`bg-green-100`, `text-red-700`) that this build does not
    generate, so `succeeded`, `failed`, `halted` and `revoked` all rendered identically as
    plain body text. See docs/10-UI-UX-AUDIT.md §1.3.
--}}
@props(['variant' => 'neutral'])

@php
    $variants = [
        'success' => 'bg-success-bg text-success-text',
        'danger' => 'bg-danger-bg text-danger-text',
        'warning' => 'bg-warning-bg text-warning-text',
        'info' => 'bg-info-bg text-info-text',
        'neutral' => 'bg-surface-muted text-content-muted',
        // §10.5 trust variants — `80g` and `verified` share brand-100/brand-800 (10.08:1).
        'trust' => 'bg-trust text-trust-text',
        'urgent' => 'bg-alert-bg text-alert-text',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold '
        .($variants[$variant] ?? $variants['neutral']),
]) }}>
    {{ $slot }}
</span>
