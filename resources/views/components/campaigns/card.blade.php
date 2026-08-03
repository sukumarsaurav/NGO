@props(['campaign'])

@php
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $shareUrl = route('campaigns.show', $campaign->slug);
@endphp

<div class="flex flex-col overflow-hidden rounded-lg border border-line-divider bg-surface">
    <a href="{{ route('campaigns.show', $campaign->slug) }}" class="block aspect-[4/3] bg-surface-muted">
        @if ($campaign->cover_image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($campaign->cover_image_path) }}" alt="{{ $campaign->cover_image_alt ?: $campaign->title }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full w-full items-center justify-center text-content-muted">{{ $campaign->category->name ?? 'Campaign' }}</div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($campaign->is_urgent)
            <x-badge variant="urgent" class="mb-2 w-fit">Urgent</x-badge>
        @endif

        <a href="{{ route('campaigns.show', $campaign->slug) }}" class="mb-1 line-clamp-2 font-semibold text-content hover:text-link">
            {{ $campaign->title }}
        </a>

        @if ($campaign->beneficiary_name)
            <p class="mb-3 text-xs text-content-muted">by {{ $campaign->beneficiary_name }}</p>
        @endif

        <div class="mt-auto">
            <x-progress-bar class="mb-1" :percent="$percent" />
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-content">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }} raised</span>
                <span class="text-content-muted">{{ $percent }}%</span>
            </div>

            <div class="mt-3 flex items-center gap-3 text-content-muted">
                <a href="https://wa.me/?text={{ urlencode($campaign->title.' '.$shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="hover:text-link">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="hover:text-link">Facebook</a>
            </div>
        </div>
    </div>
</div>
