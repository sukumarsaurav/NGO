@php
    // Uncapped: <x-progress-bar> caps the bar itself and §10.4 wants the true number
    // printed ("107% funded"), which min(100, …) used to throw away before it arrived.
    $percent = (int) round($getRecord()->percentFunded());
@endphp

<div class="w-[8rem]">
    <x-progress-bar class="mb-1" :percent="$percent" :label="$percent.'% funded'" />
    <span class="tabular text-xs text-content-muted">{{ $percent }}%</span>
</div>
