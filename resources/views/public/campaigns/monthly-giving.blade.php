<x-layout.public title="Monthly Giving">
    <div class="w-full max-w-6xl">
        <h1 class="mb-2 text-3xl font-bold text-content">Monthly Giving</h1>
        <p class="mb-6 text-content-muted">
            A small monthly gift gives these campaigns reliable, predictable support — set it up once via UPI Autopay and cancel any time.
        </p>

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
