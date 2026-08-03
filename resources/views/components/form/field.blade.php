{{--
    Form field — docs/08-DESIGN-SYSTEM.md §10.2.

    Label, control, hint and error as one unit, with the wiring §10.2 requires and none of
    the 38 hand-written controls had:

      - the label is bound to the control with `for`/`id`, so it is announced and clickable.
        Before this, the whole app had 1 label with `for=` and 32 without;
      - `required` is set as an attribute *and* shown as `*` — §10.2: "never colour or
        placement alone";
      - hint and error are joined into `aria-describedby`, and the error carries
        `role="alert"`;
      - `border-line` (3.9:1), not `border-line-divider` (1.48:1). tokens.css §"Lines" is
        explicit that the divider token is decorative and fails the 3:1 control-boundary
        requirement;
      - `text-base`. tokens.css marks 16px the "HARD FLOOR for mobile inputs" because iOS
        Safari zooms the viewport on focus below it — mid-checkout, on the money path;
      - `min-h-touch` (44px). The raw inputs measured 38.3px.

    Works for plain forms and Livewire alike: pass `wire:model` through and it lands on the
    control, and `$errors` is the same bag Livewire populates.

    See docs/10-UI-UX-AUDIT.md §1.5, §1.6, §2.3 and §2.4.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'rows' => 4,
    'id' => null,
    'errorKey' => null,
])

@php
    // `documents[]` is a valid name but not a valid id, and its error key drops the brackets.
    $key = $errorKey ?? str_replace('[]', '', $name);
    $fieldId = $id ?? preg_replace('/[^A-Za-z0-9_-]/', '-', $name);

    $hasError = $errors->has($key);
    $hintId = $hint ? $fieldId.'-hint' : null;
    $errorId = $hasError ? $fieldId.'-error' : null;
    $describedBy = trim(($hintId ?? '').' '.($errorId ?? ''));

    $control = 'block w-full min-h-touch rounded-sm border bg-surface px-3 py-2 text-base text-content '
        .'placeholder:text-content-placeholder transition-colors duration-fast focus:border-focus '
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

    @if ($type === 'textarea')
        <textarea
            id="{{ $fieldId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class')->merge(['class' => $control]) }}
        >{{ $value ?? old($key) }}</textarea>
    @else
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            @if ($type !== 'file') value="{{ $value ?? old($key) }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class')->merge(['class' => $control]) }}
        >
    @endif

    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm text-content-muted">{{ $hint }}</p>
    @endif

    @error($key)
        <p id="{{ $errorId }}" role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
