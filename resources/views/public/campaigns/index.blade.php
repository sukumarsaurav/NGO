<x-layout.public title="All Campaigns">
    <div class="w-full max-w-6xl">
        <h1 class="mb-2 text-3xl font-bold text-content">Explore Campaigns</h1>
        <p class="mb-6 text-content-muted">Every campaign here is run by a verified NGO with 80G tax benefits.</p>

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['' => 'Newest', 'most-funded' => 'Most funded', 'ending-soon' => 'Ending soon', 'urgent' => 'Urgent'] as $value => $label)
                <a
                    href="{{ route('campaigns.index', array_filter(['sort' => $value, 'category' => $activeCategory])) }}"
                    wire:navigate
                    class="rounded-full border px-4 py-2 text-sm font-medium
                        {{ $sort === $value ? 'border-action bg-action text-action-on' : 'border-line text-content hover:bg-surface-muted' }}"
                >{{ $label }}</a>
            @endforeach
        </div>

        {{-- Category filter chips — see docs/14 §2. Only rendered when categories exist,
             same empty-state guard used everywhere else a taxonomy-driven list can be
             empty. --}}
        @if ($categories->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2 border-t border-line-divider pt-4">
                <a
                    href="{{ route('campaigns.index', array_filter(['sort' => $sort])) }}"
                    wire:navigate
                    class="rounded-full px-3 py-2 text-sm font-medium
                        {{ $activeCategory === '' ? 'bg-brand-100 text-brand-700' : 'text-content-muted hover:text-content' }}"
                >All causes</a>
                @foreach ($categories as $category)
                    <a
                        href="{{ route('campaigns.index', array_filter(['sort' => $sort, 'category' => $category['slug']])) }}"
                        wire:navigate
                        class="rounded-full px-3 py-2 text-sm font-medium
                            {{ $activeCategory === $category['slug'] ? 'bg-brand-100 text-brand-700' : 'text-content-muted hover:text-content' }}"
                    >{{ $category['name'] }}</a>
                @endforeach
            </div>
        @endif

        {{-- "Showing X of Y" — flagged missing in docs/11 §2.9; orients the visitor
             after a sort/filter change without them having to count the grid. --}}
        <p class="mb-4 text-sm text-content-muted">
            @if ($campaigns->isEmpty())
                No campaigns match this filter yet.
            @else
                Showing {{ $campaigns->firstItem() }}&ndash;{{ $campaigns->lastItem() }} of {{ $campaigns->total() }} campaigns
            @endif
        </p>

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
