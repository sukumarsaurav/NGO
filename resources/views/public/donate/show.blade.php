<x-layout.public title="Donate">
    <div class="w-full max-w-lg">
        <h1 class="mb-2 text-center text-2xl font-bold text-content">Make a donation</h1>
        <p class="mb-6 text-center text-sm text-content-muted">
            Every contribution helps us continue our work.
        </p>

        @livewire('donations.donation-form')
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</x-layout.public>
