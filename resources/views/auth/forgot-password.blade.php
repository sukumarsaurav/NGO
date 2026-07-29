<x-layout.guest :title="'Forgot password — '.config('app.name')">
    <h1 class="mb-2 text-2xl font-bold text-content">Forgot your password?</h1>
    <p class="mb-6 text-sm text-content-muted">
        Enter your email and we'll send you a link to reset it.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-sm bg-success-bg px-4 py-3 text-sm text-success-text" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-form.label for="email" required>Email</x-form.label>
            <x-form.input type="email" name="email" id="email" :value="old('email')" required autofocus />
        </div>

        <x-button>Email password reset link</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        <a href="{{ route('login') }}" class="font-semibold text-link hover:text-link-hover">Back to log in</a>
    </p>
</x-layout.guest>
