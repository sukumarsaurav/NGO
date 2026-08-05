@php
    $grouped = $groupByCategory ? $partners->groupBy(fn ($p) => $p->category ?: 'Other') : ['' => $partners];
@endphp

<x-layout.public title="Partners" description="Organisations and companies we work with.">
    <div class="w-full max-w-container">
        <h1 class="mb-2 text-center text-2xl font-bold text-content sm:text-3xl">Our Partners</h1>
        <p class="mx-auto mb-8 max-w-xl text-center text-content-muted">
            We're grateful to the organisations and companies who make our work possible.
        </p>

        @if ($partners->isEmpty())
            <x-empty-state title="No partners listed yet">
                Check back soon.
            </x-empty-state>
        @else
            @foreach ($grouped as $category => $group)
                <section class="mb-10">
                    @if ($category)
                        <h2 class="mb-4 text-lg font-semibold text-content">{{ $category }}</h2>
                    @endif
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($group as $partner)
                            @if ($partner->website_url)
                                <a href="{{ $partner->website_url }}" target="_blank" rel="noopener sponsored" class="group flex flex-col items-center justify-center gap-2 rounded-lg border border-line-divider bg-surface p-4">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($partner->logo_path) }}" alt="{{ $partner->name }}" loading="lazy" class="h-16 max-w-full object-contain grayscale transition-all duration-base group-hover:grayscale-0">
                                    <span class="sr-only">{{ $partner->name }}</span>
                                </a>
                            @else
                                <div class="group flex flex-col items-center justify-center gap-2 rounded-lg border border-line-divider bg-surface p-4">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($partner->logo_path) }}" alt="{{ $partner->name }}" loading="lazy" class="h-16 max-w-full object-contain grayscale transition-all duration-base group-hover:grayscale-0">
                                    <span class="sr-only">{{ $partner->name }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
</x-layout.public>
