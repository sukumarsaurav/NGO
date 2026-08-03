{{--
    Progress bar — docs/08-DESIGN-SYSTEM.md §10.4.

    Track `surface-muted`, fill `highlight`, 8px, `radius-full`, with
    `role="progressbar"` and aria-valuenow/min/max — none of which the four inlined copies
    this replaces had.

    FILL COLOUR: §10.4 specifies `accent-500`, and tokens.css names that same value
    `--color-highlight` with the comment "progress fill". The inlined copies all used
    `bg-action` (green) instead. This follows the spec and the token.

    OVER 100%: §10.4 — "renders '107% funded' with the bar capped full". Pass the true
    percentage; the bar caps itself, and `$display` gives callers the uncapped number to
    print. Call sites used to cap with `min(100, ...)` before the value ever arrived, which
    threw the real figure away.

    §10.4 also requires the bar always be paired with a text percentage — colour and length
    alone fail the no-colour-only rule. Callers render that text; `aria-valuenow` covers
    screen readers regardless.

    Animates with `transform: scaleX()` rather than `width` so the fill stays on the
    compositor, per the transform/opacity-only rule in tokens.css §7. The outer track clips
    with `overflow-hidden rounded-full`, so scaling the inner bar cannot distort the shape.
--}}
@props([
    'percent' => 0,
    'label' => null,
])

@php
    $value = max(0, (float) $percent);
    $capped = min(100, $value);
    $display = (int) round($value);
@endphp

<div
    {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-surface-muted']) }}
    role="progressbar"
    aria-valuenow="{{ $display }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($label) aria-label="{{ $label }}" @endif
>
    <div
        class="h-full w-full origin-left bg-highlight transition-transform duration-slow ease-out"
        style="transform: scaleX({{ round($capped / 100, 4) }})"
    ></div>
</div>
