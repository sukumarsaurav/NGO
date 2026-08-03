<x-layout.public title="Payment failed" :noindex="true">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-danger-bg text-2xl text-danger-text">
            &#10007;
        </div>
        <h1 class="mb-2 text-2xl font-bold text-content">Payment didn't go through</h1>
        <p class="mb-8 text-content-muted">
            This sometimes happens with banks or network hiccups — it's not something you did wrong.
            Your details are safe; feel free to try again.
        </p>

        <div class="flex justify-center gap-3">
            <x-button :href="route('donate.show')">Try again</x-button>
        </div>

        <p class="mt-8 text-xs text-content-muted">
            Still having trouble? Contact us and we'll help you complete your donation.
        </p>
    </div>
</x-layout.public>
