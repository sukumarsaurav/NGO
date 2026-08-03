{{--
    Checkbox.

    The whole row is the label, so the tap target is the label's full width rather than the
    16×16 box — the raw checkboxes measured exactly 16px against a 44px minimum, and the
    text beside them was not clickable.

    `items-start` with a matching top margin keeps the box aligned to the first line when
    the label wraps to two, which the terms-acceptance and 80G rows both do on mobile.
--}}
@props([
    'name',
    'value' => '1',
    'checked' => false,
    'hint' => null,
    'errorKey' => null,
])

@php
    $key = $errorKey ?? $name;
    $fieldId = preg_replace('/[^A-Za-z0-9_-]/', '-', $name);
    $hasError = $errors->has($key);
    $hintId = $hint ? $fieldId.'-hint' : null;
    $errorId = $hasError ? $fieldId.'-error' : null;
    $describedBy = trim(($hintId ?? '').' '.($errorId ?? ''));
@endphp

<div>
    <label for="{{ $fieldId }}" class="flex min-h-touch cursor-pointer items-start gap-3 py-2 text-base text-content">
        <input
            type="checkbox"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @checked($checked)
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->merge(['class' => 'mt-1 h-4 w-4 shrink-0 rounded-sm border-line text-action']) }}
        >
        <span>{{ $slot }}</span>
    </label>

    @if ($hint)
        <p id="{{ $hintId }}" class="ml-6 text-sm text-content-muted">{{ $hint }}</p>
    @endif

    @error($key)
        <p id="{{ $errorId }}" role="alert" class="ml-6 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
