@props(['campaign', 'percent'])

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

    {{-- "Spread the Word" used to live here — moved to a standalone
         `<x-campaigns.share-block>` rendered lower on the page. See
         docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §2.8. --}}
    @if ($campaign->status->acceptsDonations())
        @livewire('donations.donation-form', ['campaignId' => $campaign->id])
    @else
        <p class="rounded-md bg-surface-muted p-3 text-center text-sm text-content-muted">
            This campaign is not currently accepting donations.
        </p>
    @endif
</div>
