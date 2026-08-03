<x-layout.portal title="Edit profile">
    <h1 class="mb-6 text-2xl font-bold text-content">Edit your profile</h1>

    @if (! $member)
        <x-empty-state title="No member profile">
            No member profile is linked to this account yet.
        </x-empty-state>
    @else
        @if (session('status') === 'profile-updated')
            <x-alert variant="success" class="mb-4">Profile updated.</x-alert>
        @endif

        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data"
              class="max-w-2xl space-y-6 rounded-lg border border-line-divider bg-surface p-6 shadow-sm">
            @csrf
            @method('PUT')

            <x-form.field name="photo_path" label="Photo" type="file" accept="image/*" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.field name="name" label="Name" required :value="old('name', auth()->user()->name)" autocomplete="name" />
                <x-form.field name="email" label="Email" type="email" required :value="old('email', auth()->user()->email)" autocomplete="email" />
                <x-form.field name="phone" label="Phone" type="tel" :value="old('phone', auth()->user()->phone)" autocomplete="tel" />
                <x-form.field name="emergency_contact_phone" label="Emergency contact phone" type="tel" :value="old('emergency_contact_phone', $member->emergency_contact_phone)" />
                <x-form.field name="emergency_contact_name" label="Emergency contact name" :value="old('emergency_contact_name', $member->emergency_contact_name)" />
            </div>

            <x-form.field name="address_line1" label="Address line 1" :value="old('address_line1', $member->address_line1)" autocomplete="address-line1" />
            <x-form.field name="address_line2" label="Address line 2" :value="old('address_line2', $member->address_line2)" autocomplete="address-line2" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-form.field name="city" label="City" :value="old('city', $member->city)" autocomplete="address-level2" />
                <x-form.field name="state" label="State" :value="old('state', $member->state)" autocomplete="address-level1" />
                <x-form.field name="pincode" label="Pincode" :value="old('pincode', $member->pincode)" autocomplete="postal-code" inputmode="numeric" />
            </div>

            <p class="text-sm text-content-muted">
                Department, designation and status can only be changed by an administrator.
            </p>

            <x-button size="lg">Save changes</x-button>
        </form>
    @endif
</x-layout.portal>
