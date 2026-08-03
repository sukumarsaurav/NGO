<x-layout.public title="Thank you" :noindex="true">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success-bg text-2xl text-success-text">
            &#10003;
        </div>
        <h1 class="mb-2 text-2xl font-bold text-content">Thank you!</h1>
        <p class="mb-6 text-content-muted">
            Your donation of <strong>&#8377;{{ number_format($donation->amount / 100, 2) }}</strong> was received successfully.
        </p>
        <p class="mb-8 text-sm text-content-muted">
            A receipt is on its way to your email inbox.
        </p>

        <div class="flex justify-center gap-3">
            <x-button :href="route('donate.show')">Give again</x-button>
            <x-button variant="secondary" :href="url('/')">Back to homepage</x-button>
        </div>
    </div>
</x-layout.public>
