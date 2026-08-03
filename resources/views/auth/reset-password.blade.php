<x-layout.guest :title="'Reset password — '.config('app.name')">
    <h1 class="mb-6 text-2xl font-bold text-content">Reset your password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.field name="email" label="Email" type="email" :value="old('email', $request->query('email'))" required autofocus autocomplete="username" />
        <x-form.field name="password" label="New password" type="password" required autocomplete="new-password" />
        <x-form.field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />

        <x-button size="lg" full>Reset password</x-button>
    </form>
</x-layout.guest>
