<x-layout.public title="All Campaigns">
    <div class="w-full max-w-6xl">
        <h1 class="mb-2 text-3xl font-bold text-content">Explore Campaigns</h1>
        <p class="mb-6 text-content-muted">Every campaign here is run by a verified NGO with 80G tax benefits.</p>

        <div class="mb-6 flex flex-wrap gap-2">
            @foreach (['' => 'Newest', 'most-funded' => 'Most funded', 'ending-soon' => 'Ending soon', 'urgent' => 'Urgent'] as $value => $label)
                <a
                    href="{{ route('campaigns.index', array_filter(['sort' => $value])) }}"
                    wire:navigate
                    class="rounded-full border px-4 py-2 text-sm font-medium
                        {{ $sort === $value ? 'border-action bg-action text-action-on' : 'border-line text-content hover:bg-surface-muted' }}"
                >{{ $label }}</a>
            @endforeach
        </div>

        @if ($campaigns->isEmpty())
            <x-empty-state title="No live campaigns">
                No campaigns are live right now — check back soon.
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
