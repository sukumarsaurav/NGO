<x-layout.guest :title="'Verify email — '.config('app.name')">
    <h1 class="mb-2 text-2xl font-bold text-content">Verify your email</h1>
    <p class="mb-6 text-sm text-content-muted">
        Thanks for signing up! Before getting started, check your email for a verification link.
    </p>

    @if (session('status') === 'verification-link-sent')
        <x-alert variant="success" class="mb-4">
            A new verification link has been sent to the email address you provided during registration.
        </x-alert>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button size="lg" full>Resend verification email</x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="secondary" size="lg" full>Log out</x-button>
        </form>
    </div>
</x-layout.guest>
