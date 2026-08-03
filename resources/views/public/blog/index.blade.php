<x-layout.public title="Blog" description="Stories, updates and guides from our work in the field.">
    <div class="w-full max-w-6xl">
        <h1 class="mb-6 text-3xl font-bold text-content">Blog</h1>

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}" class="rounded-full border px-4 py-2 text-sm font-medium {{ ! $activeCategory ? 'border-action bg-action text-action-on' : 'border-line text-content' }}">All</a>
            @foreach ($categories as $category)
                <a href="{{ route('blog.index', ['category' => $category->value]) }}" class="rounded-full border px-4 py-2 text-sm font-medium {{ $activeCategory === $category->value ? 'border-action bg-action text-action-on' : 'border-line text-content' }}">{{ $category->label() }}</a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <x-empty-state title="No posts yet">
                No posts yet — check back soon.
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden rounded-lg border border-line-divider bg-surface">
                        <div class="aspect-video bg-surface-muted">
                            @if ($post->cover_image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover_image_path) }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="p-4">
                            @if ($post->category)
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-action">{{ $post->category->label() }}</p>
                            @endif
                            <p class="mb-1 font-semibold text-content">{{ $post->title }}</p>
                            @if ($post->excerpt)
                                <p class="line-clamp-2 text-sm text-content-muted">{{ $post->excerpt }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layout.public>
