<x-layout.guest :title="'Forgot password — '.config('app.name')">
    <h1 class="mb-2 text-2xl font-bold text-content">Forgot your password?</h1>
    <p class="mb-6 text-sm text-content-muted">
        Enter your email and we'll send you a link to reset it.
    </p>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-form.field name="email" label="Email" type="email" required autofocus autocomplete="username" />

        <x-button size="lg" full>Email password reset link</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        <a href="{{ route('login') }}" class="font-semibold text-link transition-colors duration-fast hover:text-link-hover">Back to log in</a>
    </p>
</x-layout.guest>
