{{--
    A single YouTube embed.

    Uses youtube-nocookie.com rather than youtube.com: the standard domain sets tracking
    cookies on page load, before the visitor has clicked anything, which on a donation site
    is both a consent problem and unnecessary. The nocookie host defers that until playback.

    `loading="lazy"` matters more than usual here — a YouTube iframe pulls several hundred
    KB of player JS, and this section sits well below the fold on a page whose audience is
    largely on Indian mobile networks.

    Accepts whatever URL shape the admin pasted: watch?v=, youtu.be/, /embed/, /shorts/,
    or a bare 11-character id.
--}}
@props(['url', 'title' => null])

@php
    $videoId = null;

    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', (string) $url, $m)) {
        $videoId = $m[1];
    } elseif (preg_match('~^[A-Za-z0-9_-]{11}$~', trim((string) $url))) {
        // Admin pasted just the id.
        $videoId = trim((string) $url);
    }
@endphp

@if ($videoId)
    <figure {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm']) }}>
        {{-- aspect-video isn't in this project's replaced Tailwind scale, so the 16:9 box is
             held open with an inline padding-bottom ratio instead — prevents the layout
             shift an unsized iframe would otherwise cause. --}}
        <div class="relative w-full" style="padding-bottom: 56.25%;">
            <iframe
                src="https://www.youtube-nocookie.com/embed/{{ $videoId }}?rel=0"
                title="{{ $title ?: 'Video from Vision Good Work Global Foundation' }}"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                class="absolute inset-0 h-full w-full"
                style="border: 0;"
            ></iframe>
        </div>

        @if ($title)
            <figcaption class="p-3 text-sm text-content-muted">{{ $title }}</figcaption>
        @endif
    </figure>
@endif
