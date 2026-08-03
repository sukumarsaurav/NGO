<x-layout.public title="Mandate not approved">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-danger-bg text-2xl text-danger-text">
            &#10007;
        </div>
        <h1 class="mb-2 text-2xl font-bold text-content">Mandate not approved</h1>
        <p class="mb-8 text-content-muted">
            No worries — you can try setting up your monthly donation again any time, or make a
            one-time donation instead.
        </p>

        <div class="flex justify-center gap-3">
            <x-button :href="route('donate.show')">
                Try again
            </x-button>
        </div>
    </div>
</x-layout.public>
