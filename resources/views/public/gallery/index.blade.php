<x-layout.public title="Gallery" description="Photos from our campaigns, distributions and events.">
    <div class="w-full max-w-container">
        <h1 class="mb-2 text-center text-2xl font-bold text-content sm:text-3xl">Gallery</h1>
        <p class="mx-auto mb-8 max-w-xl text-center text-content-muted">
            Moments from our campaigns, distributions and events.
        </p>

        @if ($photos->isEmpty())
            <x-empty-state title="No photos yet">
                Check back soon — we'll be adding photos from our work here.
            </x-empty-state>
        @else
            {{-- Simple click-to-enlarge lightbox: same x-show/x-transition scrim idiom the
                 mobile nav drawer already uses (components/layout/public.blade.php), so no
                 new Alpine interaction pattern is introduced. --}}
            <div x-data="{ open: false, src: null, alt: null }" @keydown.escape.window="open = false">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $photo)
                        <button
                            type="button"
                            @click="open = true; src = '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}'; alt = @js($photo->title ?: 'Gallery photo')"
                            class="group block aspect-square overflow-hidden rounded-lg border border-line-divider bg-surface-muted"
                        >
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path) }}"
                                alt="{{ $photo->title ?: 'Gallery photo' }}"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-base group-hover:scale-105"
                            >
                            @if ($photo->title)
                                <span class="sr-only">{{ $photo->title }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition-opacity duration-base ease-out"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave="transition-opacity duration-fast ease-in-out"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-drawer flex items-center justify-center bg-scrim p-4"
                    @click="open = false"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Photo preview"
                >
                    <button
                        type="button"
                        @click="open = false"
                        class="absolute right-4 top-4 inline-flex min-h-touch min-w-touch items-center justify-center rounded-md bg-surface/90 p-2 text-content"
                        aria-label="Close preview"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                    <img :src="src" :alt="alt" @click.stop class="max-h-[85vh] max-w-full rounded-lg object-contain shadow-lg">
                </div>
            </div>
        @endif
    </div>
</x-layout.public>
