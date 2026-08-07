<x-layout.public title="Monthly Giving">
    <div class="w-full max-w-6xl">
        <h1 class="mb-2 text-3xl font-bold text-content">Monthly Giving</h1>
        <p class="mb-6 text-content-muted">
            A small monthly gift gives these campaigns reliable, predictable support — set it up once via UPI Autopay and cancel any time.
        </p>

        {{-- 3-icon benefit strip — a donor comparing one-time vs. recurring had only the
             single intro sentence above to go on. Matches the homepage's "How to Donate"
             numbered-tile pattern rather than inventing a new one. See
             docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §5. --}}
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <svg class="mx-auto mb-2 h-6 w-6 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <p class="mb-1 font-semibold text-content">Cancel anytime</p>
                <p class="text-xs text-content-muted">No lock-in — pause or stop your monthly gift whenever you need to.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <svg class="mx-auto mb-2 h-6 w-6 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mb-1 font-semibold text-content">Instant monthly receipt</p>
                <p class="text-xs text-content-muted">An 80G tax-exemption receipt lands in your inbox with every charge.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <svg class="mx-auto mb-2 h-6 w-6 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <p class="mb-1 font-semibold text-content">Predictable support</p>
                <p class="text-xs text-content-muted">Recurring gifts let these campaigns plan ahead instead of relying on one-off spikes.</p>
            </div>
        </div>

        @if ($campaigns->isEmpty())
            <x-empty-state title="No monthly giving campaigns">
                No campaigns are currently accepting monthly gifts — check back soon.
                <x-slot:action>
                    <x-button variant="secondary" :href="route('campaigns.index')">Browse one-off campaigns</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($campaigns as $campaign)
                    <x-campaigns.card :campaign="$campaign" :reveal="$loop->index" />
                @endforeach
            </div>

            <div class="mt-8">{{ $campaigns->links() }}</div>
        @endif
    </div>
</x-layout.public>
