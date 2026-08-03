<x-layout.guest :title="'Log in — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Log in</h1>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-form.field name="email" label="Email" type="email" required autofocus autocomplete="username" />
        <x-form.field name="password" label="Password" type="password" required autocomplete="current-password" />

        {{-- Side by side these two collide at 375px and "Remember me" wraps mid-phrase. --}}
        <div class="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
            <x-form.checkbox name="remember">Remember me</x-form.checkbox>

            <a href="{{ route('password.request') }}" class="text-link transition-colors duration-fast hover:text-link-hover">
                Forgot your password?
            </a>
        </div>

        <x-button size="lg" full>Log in</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-link transition-colors duration-fast hover:text-link-hover">Register</a>
    </p>
</x-layout.guest>
