@php
    $metaTitle = $category->meta_title ?: "Donate for {$category->name} — Verified Campaigns | ".config('app.name');
    $metaDescription = $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($category->intro_body ?? ''), 155, '');
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :title-is-complete="true">
    <div class="w-full max-w-6xl">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-content-muted">
            <a href="{{ url('/') }}" wire:navigate class="hover:text-link">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('campaigns.index') }}" wire:navigate class="hover:text-link">Campaigns</a>
            <span class="mx-1">/</span>
            <span>{{ $category->name }}</span>
        </nav>

        <h1 class="mb-4 text-3xl font-bold text-content">Donate for {{ $category->name }}</h1>

        @if ($category->intro_body)
            <div class="prose prose-sm mb-8 max-w-none text-content-muted">
                {!! nl2br(e($category->intro_body)) !!}
            </div>
        @endif

        @if ($campaigns->isEmpty())
            {{-- This one has a next step the visitor can actually take: other causes
                 are live even when this one is not. --}}
            <x-empty-state title="No {{ strtolower($category->name) }} campaigns right now">
                No {{ strtolower($category->name) }} campaigns are live right now — check back soon.
                <x-slot:action>
                    <x-button variant="secondary" :href="route('campaigns.index')">Browse all campaigns</x-button>
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

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Campaigns', 'item' => route('campaigns.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('campaigns.category', $category->slug)],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
</x-layout.public>
