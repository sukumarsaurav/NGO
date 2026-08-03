@php
    $metaTitle = $post->meta_title ?: "{$post->title} | ".config('app.name').' Blog';
    $metaDescription = $post->meta_description ?: $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body), 155, '');
    $coverUrl = $post->cover_image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover_image_path) : null;
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :og-image="$coverUrl" :title-is-complete="true">
    <div class="w-full max-w-2xl">
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-content-muted">
            <a href="{{ url('/') }}" class="hover:text-link">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-link">Blog</a>
            <span class="mx-1">/</span>
            <span>{{ $post->title }}</span>
        </nav>

        @if ($post->category)
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-action">{{ $post->category->label() }}</p>
        @endif
        <h1 class="mb-2 text-3xl font-bold text-content">{{ $post->title }}</h1>
        <p class="mb-6 text-sm text-content-muted">
            @if ($post->author) By {{ $post->author->name }} &middot; @endif
            {{ $post->published_at?->format('d M Y') }}
        </p>

        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $post->title }}" class="mb-6 w-full rounded-lg object-cover">
        @endif

        <div class="prose prose-sm max-w-none text-content">
            {!! str($post->body)->sanitizeHtml() !!}
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-bold text-content">Related posts</h2>
                <ul class="space-y-2">
                    @foreach ($related as $item)
                        <li><a href="{{ route('blog.show', $item->slug) }}" class="text-link hover:text-link-hover">{{ $item->title }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'image' => $coverUrl,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => route('blog.show', $post->slug)],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
</x-layout.public>
