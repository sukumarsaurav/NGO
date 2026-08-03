@php
    $metaTitle = $page->meta_title ?: "{$page->title} | ".config('app.name');
    $metaDescription = $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->body), 155, '');
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :title-is-complete="true">
    <div class="w-full max-w-2xl">
        <h1 class="mb-6 text-3xl font-bold text-content">{{ $page->title }}</h1>
        <div class="prose prose-sm max-w-none text-content">
            {!! str($page->body)->sanitizeHtml() !!}
        </div>
    </div>

    @if ($page->slug === 'about')
        <script type="application/ld+json">
            {!! json_encode(['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => $page->title, 'url' => url()->current()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
</x-layout.public>
