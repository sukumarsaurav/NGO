@if (! $gaveUp)
    <meta http-equiv="refresh" content="3;url={{ route('donate.monthly.return', ['uuid' => $subscription->uuid, 'status' => 'authorized', 'attempt' => $attempt + 1]) }}">
@endif

<x-layout.public title="Confirming with your bank">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-warning-bg text-2xl text-warning-text">
            &#8987;
        </div>

        @if (! $gaveUp)
            <h1 class="mb-2 text-2xl font-bold text-content">Confirming with your bank&hellip;</h1>
            <p class="text-content-muted">This usually takes a few seconds.</p>
        @else
            <h1 class="mb-2 text-2xl font-bold text-content">We're confirming with your bank</h1>
            <p class="text-content-muted">We'll email you as soon as your monthly donation is active.</p>
        @endif
    </div>
</x-layout.public>
