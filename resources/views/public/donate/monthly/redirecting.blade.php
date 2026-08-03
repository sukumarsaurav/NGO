<x-layout.public title="Taking you to your bank">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-info-bg text-2xl text-info-text">
            &#128274;
        </div>
        <h1 class="mb-2 text-2xl font-bold text-content">Taking you to your bank</h1>
        <p class="mb-8 text-content-muted">
            To approve &#8377;{{ number_format($subscription->amount / 100, 2) }}/{{ $subscription->interval->label() }},
            you'll authorise this with your bank or UPI app on the next screen. You can cancel any time.
        </p>

        <x-button size="xl" :href="$authUrl">
            Continue to authorise
        </x-button>
    </div>
</x-layout.public>
