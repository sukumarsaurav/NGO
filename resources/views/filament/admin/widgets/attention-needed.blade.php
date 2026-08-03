@php $counts = $this->counts(); @endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Attention needed">
        <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:1rem;">
            <div style="border-radius:0.5rem;border:1px solid #d9d4c6;padding:0.75rem;{{ $counts['halted_subscriptions'] > 0 ? 'background:#f9e7e4;' : '' }}">
                <p style="font-size:0.75rem;color:#55524a;">Halted subscriptions</p>
                <p style="font-size:1.25rem;font-weight:700;{{ $counts['halted_subscriptions'] > 0 ? 'color:#8a2a1f;' : '' }}">{{ $counts['halted_subscriptions'] }}</p>
            </div>
            <div style="border-radius:0.5rem;border:1px solid #d9d4c6;padding:0.75rem;{{ $counts['failed_jobs'] > 0 ? 'background:#f9e7e4;' : '' }}">
                <p style="font-size:0.75rem;color:#55524a;">Failed PDF jobs</p>
                <p style="font-size:1.25rem;font-weight:700;{{ $counts['failed_jobs'] > 0 ? 'color:#8a2a1f;' : '' }}">{{ $counts['failed_jobs'] }}</p>
            </div>
            <div style="border-radius:0.5rem;border:1px solid #d9d4c6;padding:0.75rem;{{ $counts['bounced_receipts'] > 0 ? 'background:#f9e7e4;' : '' }}">
                <p style="font-size:0.75rem;color:#55524a;">Bounced receipt emails</p>
                <p style="font-size:1.25rem;font-weight:700;{{ $counts['bounced_receipts'] > 0 ? 'color:#8a2a1f;' : '' }}">{{ $counts['bounced_receipts'] }}</p>
            </div>
            <div style="border-radius:0.5rem;border:1px solid #d9d4c6;padding:0.75rem;{{ $counts['donors_missing_pan'] > 0 ? 'background:#fdf8f2;' : '' }}">
                <p style="font-size:0.75rem;color:#55524a;">Donors missing PAN (80G)</p>
                <p style="font-size:1.25rem;font-weight:700;{{ $counts['donors_missing_pan'] > 0 ? 'color:#70450f;' : '' }}">{{ $counts['donors_missing_pan'] }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
