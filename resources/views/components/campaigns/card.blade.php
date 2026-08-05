@props(['campaign'])

@php
    $percent = (int) round($campaign->percentFunded());   // uncapped — <x-progress-bar> caps the bar, §10.4 prints the true number
    $shareUrl = route('campaigns.show', $campaign->slug);
@endphp

<div class="flex flex-col overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm">
    <a href="{{ route('campaigns.show', $campaign->slug) }}" class="relative block aspect-[4/3] bg-surface-muted">
        @if ($campaign->cover_image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($campaign->cover_image_path) }}" alt="{{ $campaign->cover_image_alt ?: $campaign->title }}" class="h-full w-full object-cover">
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
                    <p class="text-xs uppercase tracking-wide text-content-muted">Goal</p>
                    <p class="font-semibold text-content">₹{{ number_format($campaign->goal_amount / 100) }}</p>
                </div>
            </div>

            <x-progress-bar class="mb-1" :percent="$percent" />

            <div class="mb-3 flex items-center justify-between text-xs text-content-muted">
                <span>{{ number_format($campaign->donor_count) }} donors</span>
                <span class="font-semibold text-content">{{ $percent }}% funded</span>
            </div>

            <x-button :href="route('campaigns.show', $campaign->slug)" variant="accent" size="lg" :pill="true" full class="mb-3">
                Donate Now
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </x-button>

            <div class="flex items-center justify-between border-t border-line-divider/60 pt-2.5 px-1 text-content-muted">
                <span class="text-xs font-medium text-content-muted">Share:</span>
                <div class="flex items-center gap-2">
                    <a href="https://wa.me/?text={{ urlencode($campaign->title.' '.$shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-muted text-content-muted transition-colors hover:bg-[#25D366]/10 hover:text-[#25D366]" title="Share on WhatsApp">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.39c1.44.78 3.06 1.2 4.76 1.2h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.77 14.08c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.12.11-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.24-4.76-4.14-4.9-4.33-.14-.19-1.18-1.57-1.18-3 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.53.72 1.8.86.27.14.44.2.51.31.07.12.07.68-.17 1.36z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-muted text-content-muted transition-colors hover:bg-[#1877F2]/10 hover:text-[#1877F2]" title="Share on Facebook">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.5h2.5l.5-3h-3V8.25c0-.87.24-1.46 1.49-1.46H16.5V4.14C16.17 4.1 15.05 4 13.75 4c-2.73 0-4.6 1.66-4.6 4.7v2.8H6.5v3h2.65V21h4.35z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($campaign->title) }}" target="_blank" rel="noopener" aria-label="Share on X" class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-muted text-content-muted transition-colors hover:bg-gray-900/10 hover:text-gray-900 dark:hover:text-white" title="Share on X">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.53 3H21l-7.5 8.57L22 21h-6.83l-5.35-6.6L3.6 21H0l8.03-9.17L2 3h6.99l4.84 6.03L17.53 3zm-1.2 16.2h1.9L7.77 4.7H5.73l10.6 14.5z"/></svg>
                    </a>
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        aria-label="Copy link"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-muted text-content-muted transition-colors hover:bg-brand-500/10 hover:text-brand-600 relative"
                        title="Copy link"
                    >
                        <template x-if="!copied">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </template>
                        <template x-if="copied">
                            <svg class="h-4 w-4 text-success-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
