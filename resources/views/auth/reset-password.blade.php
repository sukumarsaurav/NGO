<x-layout.guest :title="'Reset password — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Reset your password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-form.label for="email" required>Email</x-form.label>
            <x-form.input type="email" name="email" id="email" :value="old('email', $request->query('email'))" required autofocus />
        </div>

        <div>
            <x-form.label for="password" required>New password</x-form.label>
            <x-form.input type="password" name="password" id="password" required autocomplete="new-password" />
        </div>

        <div>
            <x-form.label for="password_confirmation" required>Confirm new password</x-form.label>
            <x-form.input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-button>Reset password</x-button>
    </form>
</x-layout.guest>
