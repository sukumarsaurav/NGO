<x-layout.guest :title="'Register — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Create an account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-form.label for="name" required>Full name</x-form.label>
            <x-form.input type="text" name="name" id="name" :value="old('name')" required autofocus autocomplete="name" />
        </div>

        <div>
            <x-form.label for="email" required>Email</x-form.label>
            <x-form.input type="email" name="email" id="email" :value="old('email')" required autocomplete="username" />
        </div>

        <div>
            <x-form.label for="password" required>Password</x-form.label>
            <x-form.input type="password" name="password" id="password" required autocomplete="new-password" />
        </div>

        <div>
            <x-form.label for="password_confirmation" required>Confirm password</x-form.label>
            <x-form.input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-button>Register</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-link hover:text-link-hover">Log in</a>
    </p>
</x-layout.guest>
