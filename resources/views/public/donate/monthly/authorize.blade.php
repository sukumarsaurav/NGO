<x-layout.public title="Approve mandate (demo)">
    <div class="w-full max-w-md text-center">
        <div class="mb-6 rounded-md bg-warning-bg p-3 text-xs text-warning-text">
            This page stands in for Razorpay's real hosted authentication page — it only appears
            because no real Razorpay keys are configured yet. See PaymentServiceProvider.
        </div>

        <h1 class="mb-2 text-xl font-bold text-content">Approve this mandate?</h1>
        <p class="mb-8 text-content-muted">
            &#8377;{{ number_format($subscription->amount / 100, 2) }} every {{ strtolower($subscription->interval->label()) }},
            starting today.
        </p>

        <div class="flex justify-center gap-3">
            <form method="POST" action="{{ route('donate.monthly.complete', $subscription->uuid) }}">
                @csrf
                <input type="hidden" name="decision" value="approve">
                <x-button size="lg">Approve</x-button>
            </form>
            <form method="POST" action="{{ route('donate.monthly.complete', $subscription->uuid) }}">
                @csrf
                <input type="hidden" name="decision" value="decline">
                <x-button variant="secondary" size="lg">Decline</x-button>
            </form>
        </div>
    </div>
</x-layout.public>
