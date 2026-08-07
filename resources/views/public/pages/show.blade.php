@php
    $metaTitle = $page->meta_title ?: "{$page->title} | ".config('app.name');
    $metaDescription = $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->body), 155, '');
@endphp

<x-layout.public :title="$metaTitle" :description="$metaDescription" :title-is-complete="true">
    @if ($page->slug === 'about')
        {{-- About is a trust surface, not just a text page — previously a bare heading and
             four paragraphs on an otherwise empty page. A hero band gives it a floor above
             "unstyled text file," matching the monthly-giving promo band's full-bleed
             dark-tinted-photo treatment. See
             docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §6. --}}
        <section class="relative mb-8 w-screen overflow-hidden px-6 py-12 text-center sm:py-16" style="margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);">
            <img
                src="{{ asset('images/hero-community.png') }}"
                alt=""
                aria-hidden="true"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-brand-900/80"></div>
            <div class="relative">
                <h1 class="font-heading text-3xl font-bold text-white sm:text-4xl">{{ $page->title }}</h1>
            </div>
        </section>
    @endif

    <div class="w-full max-w-2xl">
        @unless ($page->slug === 'about')
            <h1 class="mb-6 text-3xl font-bold text-content">{{ $page->title }}</h1>
        @endunless

        {{-- Fade-in on scroll, matching the audit's "small amount of pacing" recommendation
             — one unit rather than per-paragraph, since $page->body is sanitized rich-text
             HTML from the CMS editor and splitting it into individually-animated blocks
             would mean parsing admin-authored markup, too fragile for the payoff. --}}
        <div
            x-data="{ shown: false }"
            x-intersect.once="shown = true"
            :class="shown ? 'opacity-100' : 'opacity-0'"
            class="prose prose-sm max-w-none text-content transition-opacity duration-slow"
        >
            {!! str($page->body)->sanitizeHtml() !!}
        </div>

        @if ($page->slug === 'about' && $impactStats->isNotEmpty())
            {{-- Repeats 2-3 of the homepage's own impact numbers here with more context —
                 reusing existing data rather than inventing new content. --}}
            <div class="mt-8 border-t border-line-divider pt-8">
                <p class="mb-4 text-sm font-semibold uppercase tracking-wide text-content-muted">The numbers behind our work</p>
                <x-impact-stats :stats="$impactStats" />
            </div>
        @endif
    </div>

    @if ($page->slug === 'about')
        <script type="application/ld+json">
            {!! json_encode(['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => $page->title, 'url' => url()->current()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
</x-layout.public>
