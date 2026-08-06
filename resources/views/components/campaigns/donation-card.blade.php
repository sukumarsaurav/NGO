@props(['campaign', 'percent'])

@php
    $hasTaxBenefit = (bool) $campaign->is_tax_benefit;
    $hasLiveUpdates = $campaign->updates->isNotEmpty();
    $pillCount = ($hasTaxBenefit ? 1 : 0) + ($hasLiveUpdates ? 1 : 0);
@endphp

{{--
    Sidebar card — see docs/13-CAMPAIGN-DETAIL-DESIGN-AUDIT-VS-REFERENCE.md PR A/B.

    `p-6 shadow-md` (was `p-4 shadow-sm`) — matches the reference's measured 24px padding
    and its actual card shadow; `shadow-sm` was barely visible next to the reference's
    `0 4px 6px -1px, 0 2px 4px -2px`, which `--shadow-md` already models.
--}}
<div class="rounded-lg border border-line-divider bg-surface p-6 shadow-md">
    {{-- Trust pills promoted to the top of the card, equal width, `grid-cols-2` when both
         apply — previously small badges sitting between the pills' natural position and
         the raised/goal numbers, well below the reference's prominence for the same two
         signals. A lone pill (only one of the two applies) spans full width rather than
         sitting half-width next to an empty grid cell. --}}
    @if ($pillCount > 0)
        <div class="mb-4 grid {{ $pillCount > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
            @if ($hasTaxBenefit)
                <div class="flex items-center justify-center gap-2 rounded-full border border-line-divider bg-surface p-2 text-xs font-semibold text-content">
                    <svg class="h-4 w-4 shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Tax Benefit
                </div>
            @endif
            @if ($hasLiveUpdates)
                <div class="flex items-center justify-center gap-2 rounded-full border border-line-divider bg-surface p-2 text-xs font-semibold text-content">
                    <svg class="h-4 w-4 shrink-0 text-highlight" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M11.983 1.907a.75.75 0 00-1.292-.657L4.845 9.75H2.75a.75.75 0 00-.6 1.2l4.5 6a.75.75 0 001.2 0l4.5-6a.75.75 0 00-.6-1.2h-2.096l3.63-6.343a.75.75 0 00.699-.5z" />
                    </svg>
                    Live Updates
                </div>
            @endif
        </div>
    @endif

    {{-- Raised/goal numbers get their own sub-surface (`bg-surface-muted`, the token
         closest to the reference's measured `#f5f3ee`) instead of printing directly on the
         card background — the reference frames the money figures as a distinct unit. --}}
    <div class="mb-4 rounded-lg border border-line-divider bg-surface-muted p-4">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-content-muted">Raised so far</p>
                <p class="font-heading text-2xl font-bold text-brand-700">₹{{ number_format($campaign->displayedRaisedAmount() / 100) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wide text-content-muted">Goal amount</p>
                <p class="font-heading text-2xl font-bold text-content">₹{{ number_format($campaign->goal_amount / 100) }}</p>
            </div>
        </div>

        <x-progress-bar class="mt-3 mb-1" :percent="$percent" />
        <div class="flex items-center justify-between text-xs text-content-muted">
            <span>{{ $campaign->donor_count }} donors</span>
            <span class="font-semibold text-content">{{ $percent }}% funded</span>
        </div>
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
