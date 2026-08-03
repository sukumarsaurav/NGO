<x-layout.guest :title="'Register — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Create an account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-form.field name="name" label="Full name" required autofocus autocomplete="name" />
        <x-form.field name="email" label="Email" type="email" required autocomplete="username" />
        <x-form.field name="password" label="Password" type="password" required autocomplete="new-password" />
        <x-form.field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <x-button size="lg" full>Register</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-link transition-colors duration-fast hover:text-link-hover">Log in</a>
    </p>
</x-layout.guest>
