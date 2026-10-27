{{--
    The organisation's public registration identifiers — the numbers a donor can check against the
    Ministry of Corporate Affairs and the Income Tax Department to confirm the foundation is real.
    Used in the campaign page's "NGO Legal Verification" box and at the top of the Certificates
    page, so both always show the same facts from the same settings.

    Every identifier but the CIN is hidden until it has been entered — a blank row would read as
    something withheld. The PAN additionally needs `org.pan_public` switched on: it is stored
    encrypted and masked in the admin, so showing it is a deliberate, reversible choice.
    12A / 80G are NOT here; they are claims, handled by App\Support\TaxExemption.
--}}
@props(['note' => true])

@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);

    $incorporated = $settings->get('org.registration_date');
    try {
        $incorporated = $incorporated ? \Illuminate\Support\Carbon::parse($incorporated)->format('d M Y') : null;
    } catch (\Throwable) {
        // A free-text date an admin typed that does not parse: show it as written.
        $incorporated = $settings->get('org.registration_date');
    }

    $rows = array_filter([
        'Company Registration (CIN)' => $settings->get('org.registration_number') ?: 'Not yet provided',
        'Section 8 Licence No.' => $settings->get('org.section8_licence_number'),
        'Date of Incorporation' => $incorporated,
        'PAN' => $settings->get('org.pan_public') ? $settings->get('org.pan') : null,
        'TAN' => $settings->get('org.tan'),
    ], fn ($value) => filled($value));
@endphp

<div {{ $attributes->merge(['class' => 'space-y-3 text-sm text-content']) }}>
    @foreach ($rows as $label => $value)
        <div class="flex justify-between gap-3 border-b border-line-divider/50 py-1">
            <span class="text-content-muted">{{ $label }}</span>
            <span class="font-mono text-xs bg-success-bg text-success-text px-2 py-1 rounded">{{ $value }}</span>
        </div>
    @endforeach
    <div class="flex justify-between gap-3 border-b border-line-divider/50 py-1">
        <span class="text-content-muted">Legal Status</span>
        <span class="font-semibold">Section 8 Non-Profit Company</span>
    </div>
    @if ($note)
        <p class="pt-1 text-xs text-content-muted">
            You can check these on the
            <a href="https://www.mca.gov.in" target="_blank" rel="noopener noreferrer" class="font-semibold text-link underline">Ministry of Corporate Affairs website</a>
            using the CIN.
        </p>
    @endif
</div>
