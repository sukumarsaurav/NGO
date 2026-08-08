<x-filament-widgets::widget>
    <x-filament::section heading="Top campaigns">
        @php $campaigns = $this->campaigns(); @endphp

        @if (empty($campaigns))
            <p style="font-size:0.875rem;color:#55524a;">No active campaigns yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @foreach ($campaigns as $campaign)
                    <div>
                        <div style="margin-bottom:0.25rem;display:flex;align-items:center;justify-content:space-between;font-size:0.875rem;">
                            <span style="font-weight:500;">{{ $campaign['title'] }}</span>
                            <span style="color:#55524a;">&#8377;{{ number_format($campaign['raised'] / 100, 0) }} / &#8377;{{ number_format($campaign['goal'] / 100, 0) }}</span>
                        </div>
                        <div style="height:0.5rem;width:100%;overflow:hidden;border-radius:9999px;background:#f0eee7;">
                            <div style="height:0.5rem;border-radius:9999px;background:#0d7c2f;width:{{ min($campaign['percent'], 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
