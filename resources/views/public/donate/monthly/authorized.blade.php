<x-layout.public title="Monthly donation set up">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success-bg text-2xl text-success-text">
            &#10003;
        </div>
        <h1 class="mb-2 text-2xl font-bold text-content">Your monthly donation is set up</h1>
        <p class="mb-2 text-content-muted">
            &#8377;{{ number_format($subscription->amount / 100, 2) }} every {{ strtolower($subscription->interval->label()) }}.
        </p>
        @if ($subscription->next_charge_at)
            <p class="mb-8 text-sm text-content-muted">Next charge: {{ $subscription->next_charge_at->format('d M Y') }}</p>
        @endif

        <x-button :href="route('portal.subscriptions.index')">
            Manage in your portal
        </x-button>
    </div>
</x-layout.public>
