@props(['campaign', 'percent'])

@php
    $shareUrl = route('campaigns.show', $campaign->slug);
@endphp

<div class="rounded-lg border border-line-divider bg-surface p-4 shadow-sm">
    <div class="mb-4 flex flex-wrap gap-2">
        @if ($campaign->is_tax_benefit)
            <x-badge variant="trust">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Tax Benefit
            </x-badge>
        @endif
        @if ($campaign->updates->isNotEmpty())
            <x-badge variant="info">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M11.983 1.907a.75.75 0 00-1.292-.657L4.845 9.75H2.75a.75.75 0 00-.6 1.2l4.5 6a.75.75 0 001.2 0l4.5-6a.75.75 0 00-.6-1.2h-2.096l3.63-6.343a.75.75 0 00.699-.5z" />
                </svg>
                Live Updates
            </x-badge>
        @endif
    </div>

    <div class="mb-2 flex items-start justify-between">
        <div>
            <p class="text-xs uppercase tracking-wide text-content-muted">Raised so far</p>
            <p class="font-heading text-2xl font-bold text-brand-700">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase tracking-wide text-content-muted">Goal amount</p>
            <p class="font-heading text-2xl font-bold text-content">₹{{ number_format($campaign->goal_amount / 100) }}</p>
        </div>
    </div>

    <x-progress-bar class="mb-1" :percent="$percent" />
    <div class="mb-4 flex items-center justify-between text-xs text-content-muted">
        <span>{{ $campaign->donor_count }} donors</span>
        <span class="font-semibold text-content">{{ $percent }}% funded</span>
    </div>

    @if ($campaign->status->acceptsDonations())
        @livewire('donations.donation-form', ['campaignId' => $campaign->id])
    @else
        <p class="rounded-md bg-surface-muted p-3 text-center text-sm text-content-muted">
            This campaign is not currently accepting donations.
        </p>
    @endif

    <div class="mt-4 border-t border-line-divider pt-4">
        <p class="mb-1 text-sm font-semibold text-content">Spread the Word</p>
        <p class="mb-3 text-xs text-content-muted">Sharing {{ $campaign->title }} doubles its chances of getting help.</p>
        <div class="grid grid-cols-4 gap-2">
            <a href="https://wa.me/?text={{ urlencode($campaign->title.' '.$shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="flex flex-col items-center gap-1 rounded-lg border border-line-divider p-2 text-content-muted hover:border-action hover:text-action">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.39c1.44.78 3.06 1.2 4.76 1.2h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.77 14.08c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.12.11-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.24-4.76-4.14-4.9-4.33-.14-.19-1.18-1.57-1.18-3 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.65.5.24.58.82 2 .89 2.14.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.53.72 1.8.86.27.14.44.2.51.31.07.12.07.68-.17 1.36z"/></svg>
                <span class="text-[0.65rem]">WhatsApp</span>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="flex flex-col items-center gap-1 rounded-lg border border-line-divider p-2 text-content-muted hover:border-action hover:text-action">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.5h2.5l.5-3h-3V8.25c0-.87.24-1.46 1.49-1.46H16.5V4.14C16.17 4.1 15.05 4 13.75 4c-2.73 0-4.6 1.66-4.6 4.7v2.8H6.5v3h2.65V21h4.35z"/></svg>
                <span class="text-[0.65rem]">Facebook</span>
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($campaign->title) }}" target="_blank" rel="noopener" aria-label="Share on X" class="flex flex-col items-center gap-1 rounded-lg border border-line-divider p-2 text-content-muted hover:border-action hover:text-action">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.53 3H21l-7.5 8.57L22 21h-6.83l-5.35-6.6L3.6 21H0l8.03-9.17L2 3h6.99l4.84 6.03L17.53 3zm-1.2 16.2h1.9L7.77 4.7H5.73l10.6 14.5z"/></svg>
                <span class="text-[0.65rem]">X</span>
            </a>
            <button
                type="button"
                x-data
                @click="navigator.clipboard.writeText('{{ $shareUrl }}'); $el.querySelector('span').textContent = 'Copied!'; setTimeout(() => $el.querySelector('span').textContent = 'Copy Link', 1500)"
                aria-label="Copy link"
                class="flex flex-col items-center gap-1 rounded-lg border border-line-divider p-2 text-content-muted hover:border-action hover:text-action"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                <span class="text-[0.65rem]">Copy Link</span>
            </button>
        </div>
    </div>
</div>
