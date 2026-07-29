@props(['variant' => 'primary'])

@php
    $base = 'inline-flex min-h-touch w-full items-center justify-center rounded-md px-4 py-2 text-base '
        .'font-semibold transition-colors duration-fast focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-action text-action-on hover:bg-action-hover active:bg-action-active',
        'secondary' => 'bg-brand-50 text-brand-700 hover:bg-brand-100',
    ];
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $base.' '.$variants[$variant]]) }}>
    {{ $slot }}
</button>
