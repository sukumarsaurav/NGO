@props(['required' => false])

<label {{ $attributes->merge(['class' => 'mb-1 block text-sm font-semibold text-content']) }}>
    {{ $slot }}
    @if ($required)
        <span class="text-danger">*</span>
    @endif
</label>
