{{--
    Select — the §10.2 field anatomy applied to a <select>. Shares every rule with
    <x-form.field>: bound label, `required` as attribute and `*`, aria-describedby wiring,
    `border-line` for the 3:1 control edge, 16px text, 44px minimum height.

    `options` is an id => label map. `placeholder` renders a disabled, non-selectable first
    option so an unmade choice is visible rather than looking like a default.
--}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'hint' => null,
    'placeholder' => null,
    'id' => null,
    'errorKey' => null,
])

@php
    $key = $errorKey ?? $name;
    $fieldId = $id ?? preg_replace('/[^A-Za-z0-9_-]/', '-', $name);

    $hasError = $errors->has($key);
    $hintId = $hint ? $fieldId.'-hint' : null;
    $errorId = $hasError ? $fieldId.'-error' : null;
    $describedBy = trim(($hintId ?? '').' '.($errorId ?? ''));

    $selected = $value ?? old($key);

    $control = 'block w-full min-h-touch rounded-sm border bg-surface px-3 py-2 text-base text-content '
        .'transition-colors duration-fast focus:border-focus '
        .'disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-content-disabled '
        .($hasError ? 'border-danger' : 'border-line');
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $fieldId }}" class="mb-3 block text-sm font-semibold text-content">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        @endif
    </label>

    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->merge(['class' => $control]) }}
    >
        @if ($placeholder)
            <option value="" disabled @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm text-content-muted">{{ $hint }}</p>
    @endif

    @error($key)
        <p id="{{ $errorId }}" role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
