@props(['campaign', 'percent'])

<div class="rounded-lg border border-line-divider bg-surface p-4 shadow-sm">
    <div class="mb-3 flex gap-2">
        @if ($campaign->is_tax_benefit)
            <x-badge variant="trust">Tax Benefit</x-badge>
        @endif
        <x-badge variant="neutral">{{ $campaign->updates->count() }} Updates</x-badge>
    </div>

    <div class="mb-1 flex items-baseline justify-between">
        <span class="text-lg font-bold text-content">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }}</span>
        <span class="text-sm text-content-muted">{{ $campaign->donor_count }} donors</span>
    </div>
    <p class="mb-2 text-xs text-content-muted">raised of ₹{{ number_format($campaign->goal_amount / 100) }} goal</p>

    <x-progress-bar class="mb-1" :percent="$percent" />
    <p class="mb-4 text-sm font-semibold text-content">{{ $percent }}% Complete</p>

    @if ($campaign->status->acceptsDonations())
        @livewire('donations.donation-form', ['campaignId' => $campaign->id])
    @else
        <p class="rounded-md bg-surface-muted p-3 text-center text-sm text-content-muted">
            This campaign is not currently accepting donations.
        </p>
    @endif

    <div class="mt-4 flex items-center justify-center gap-3 border-t border-line-divider pt-3 text-content-muted">
        <span class="text-xs">Share</span>
        <a href="https://wa.me/?text={{ urlencode($campaign->title.' '.route('campaigns.show', $campaign->slug)) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="hover:text-link">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('campaigns.show', $campaign->slug)) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="hover:text-link">Facebook</a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('campaigns.show', $campaign->slug)) }}&text={{ urlencode($campaign->title) }}" target="_blank" rel="noopener" aria-label="Share on X" class="hover:text-link">X</a>
    </div>
</div>
