<x-layout.public title="Confirming your payment" :noindex="true">
    <div class="w-full max-w-md text-center">
        @livewire('donations.donation-pending', ['donationUuid' => $donation->uuid])
    </div>
</x-layout.public>
