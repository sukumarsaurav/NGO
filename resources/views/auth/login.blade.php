<x-layout.guest :title="'Log in — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Log in</h1>

    @if (session('status'))
        <div class="mb-4 rounded-sm bg-success-bg px-4 py-3 text-sm text-success-text" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-form.label for="email" required>Email</x-form.label>
            <x-form.input type="email" name="email" id="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <div>
            <x-form.label for="password" required>Password</x-form.label>
            <x-form.input type="password" name="password" id="password" required autocomplete="current-password" />
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-content-muted">
                <input type="checkbox" name="remember" class="rounded-sm border-line">
                Remember me
            </label>

            <a href="{{ route('password.request') }}" class="text-link hover:text-link-hover">
                Forgot your password?
            </a>
        </div>

        <x-button>Log in</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-link hover:text-link-hover">Register</a>
    </p>
</x-layout.guest>
