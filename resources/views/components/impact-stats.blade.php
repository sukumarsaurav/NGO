{{--
    Impact stat tiles with count-up-on-scroll — extracted from the homepage so `/donate`
    (see docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §4) can reuse the same numbers, and
    the count-up/no-JS-fallback logic exists in exactly one place rather than being
    duplicated wherever this grid is needed next.

    Width-locked (`min-w-[7rem]`) to avoid CLS from the count-up. Hover-lift matches the
    "Browse by Cause" tile treatment on the homepage for visual consistency.
--}}
@props(['stats'])

@if ($stats->isNotEmpty())
    {{-- Tailwind's scanner only detects LITERAL class strings in source, so the column
         count must be one of a fixed set it can actually see — not an interpolated
         `sm:grid-cols-{{ $n }}`, which would never get generated. --}}
    @php
        $statColsClass = match (min(4, max(1, $stats->count()))) {
            1 => 'sm:grid-cols-1',
            2 => 'sm:grid-cols-2',
            3 => 'sm:grid-cols-3',
            default => 'sm:grid-cols-4',
        };
    @endphp
    <div {{ $attributes->merge(['class' => "grid grid-cols-2 gap-4 {$statColsClass}"]) }}>
        @foreach ($stats as $stat)
            {{-- Count-up on scroll-into-view. `value` is admin-entered free text (see
                 ImpactStat), not guaranteed numeric — "95+" is a real seeded value, and
                 nothing stops an admin typing "Many" instead. Animate only when stripping
                 commas leaves nothing but digits; otherwise fall back to rendering the
                 stored string as-is, unanimated. --}}
            @php
                $statDigits = str_replace(',', '', $stat->value);
                $statIsNumeric = $statDigits !== '' && ctype_digit($statDigits);
            @endphp
            <div
                class="min-w-[7rem] rounded-lg border border-line-divider bg-surface p-4 text-center shadow-sm transition-shadow duration-base hover:-translate-y-px hover:shadow-md sm:p-6"
                @if ($statIsNumeric)
                    x-data="countUpStat({{ (int) $statDigits }})"
                    x-intersect.once="start()"
                @endif
            >
                {{-- The span's initial content is the real, final value (server-rendered,
                     matches what a no-JS visitor sees permanently) so there is nothing to
                     flash-to-blank between first paint and Alpine hydration — `x-text` only
                     overwrites it once `start()` actually begins counting. --}}
                <p class="tabular font-heading text-2xl font-bold text-brand-700 sm:text-3xl">@if ($statIsNumeric)<span x-text="display">{{ $stat->value }}</span>@else{{ $stat->value }}@endif{{ $stat->suffix }}</p>
                <p class="text-xs uppercase tracking-wide text-content-muted">{{ $stat->label }}</p>
            </div>
        @endforeach
    </div>
@endif
