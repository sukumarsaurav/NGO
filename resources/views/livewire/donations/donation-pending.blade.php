<div @unless($gaveUp) wire:poll.3s="poll" @endunless>
    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-warning-bg text-2xl text-warning-text">
        &#8987;
    </div>

    @if (! $gaveUp)
        <h1 class="mb-2 text-2xl font-bold text-content">Confirming your payment&hellip;</h1>
        <p class="text-content-muted">This usually takes a few seconds. Please don't close this page.</p>
    @else
        <h1 class="mb-2 text-2xl font-bold text-content">Payment is being confirmed</h1>
        <p class="text-content-muted">
            We'll email your receipt within a few minutes once it's confirmed.
        </p>
    @endif
</div>
