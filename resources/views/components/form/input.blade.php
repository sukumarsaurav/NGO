@props(['error' => null, 'name' => null])

@php
    $hasError = $error || ($name && $errors->has($name));
    $errorId = $name ? $name.'-error' : null;
@endphp

<input {{ $attributes->merge([
    'class' => 'block min-h-touch w-full rounded-sm border bg-surface px-3 py-2 text-base text-content '
        .'placeholder:text-content-placeholder focus-visible:outline-none disabled:cursor-not-allowed '
        .'disabled:bg-surface-muted disabled:text-content-disabled '
        .($hasError ? 'border-danger' : 'border-line'),
]) }}
    @if ($name) name="{{ $name }}" @endif
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
/>

@if ($name && $errors->has($name))
    <x-form.error :id="$errorId" :name="$name" />
@endif
