@props(['campaign'])

@php
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $shareUrl = route('campaigns.show', $campaign->slug);
@endphp

<div class="flex flex-col overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm">
    <a href="{{ route('campaigns.show', $campaign->slug) }}" class="relative block aspect-[4/3] bg-surface-muted">
        @if ($campaign->cover_image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($campaign->cover_image_path) }}" alt="{{ $campaign->cover_image_alt ?: $campaign->title }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center text-content-muted">{{ $campaign->category->name ?? 'Campaign' }}</div>
        @endif

        {{-- Overlay badges sit on the image, not inline below it — matches the
             reference layout's severity/category-at-a-glance pattern. --}}
        <div class="absolute inset-x-3 top-3 flex items-start justify-between gap-2">
            @if ($campaign->is_urgent)
                <x-badge variant="urgent" class="shadow-sm">Urgent</x-badge>
            @else
                <span></span>
            @endif

            @if ($campaign->category)
                <x-badge variant="info" class="shadow-sm">{{ $campaign->category->name }}</x-badge>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('campaigns.show', $campaign->slug) }}" class="mb-1 line-clamp-2 font-heading font-bold text-content hover:text-link">
            {{ $campaign->title }}
        </a>

        <div class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-content-muted">
            @if ($campaign->beneficiary_name)
                <span>by {{ $campaign->beneficiary_name }}</span>
            @endif
            @if ($campaign->is_tax_benefit)
                <x-badge variant="trust">Tax Benefit</x-badge>
            @endif
        </div>

        @if ($campaign->subtitle)
            <p class="mb-3 line-clamp-2 text-sm text-content-muted">{{ $campaign->subtitle }}</p>
        @endif

        <div class="mt-auto">
            <div class="mb-2 flex items-center justify-between text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Raised</p>
                    <p class="font-semibold text-content">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-content-muted">Donors</p>
                    <p class="font-semibold text-content">{{ number_format($campaign->donor_count) }}</p>
                </div>
            </div>

            <x-progress-bar class="mb-3" :percent="$percent" />

            <x-button :href="route('campaigns.show', $campaign->slug)" variant="accent" size="lg" :pill="true" full class="mb-2">
                Donate Now
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </x-button>

            <div class="flex items-center justify-center gap-3 text-content-muted">
                <a href="https://wa.me/?text={{ urlencode($campaign->title.' '.$shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="text-xs hover:text-link">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="text-xs hover:text-link">Facebook</a>
            </div>
        </div>
    </div>
</div>
