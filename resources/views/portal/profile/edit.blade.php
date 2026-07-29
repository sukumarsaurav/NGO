<x-layout.portal title="Edit profile">
    <h1 class="mb-6 text-2xl font-bold text-content">Edit your profile</h1>

    @if (! $member)
        <p class="text-content-muted">No member profile is linked to this account yet.</p>
    @else
        @if (session('status') === 'profile-updated')
            <div class="mb-4 rounded-sm bg-success-bg px-4 py-3 text-sm text-success-text" role="status">
                Profile updated.
            </div>
        @endif

        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data"
              class="max-w-2xl space-y-6 rounded-lg border border-line-divider bg-surface p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <x-form.label for="photo_path">Photo</x-form.label>
                <input type="file" name="photo_path" id="photo_path" accept="image/*"
                       class="block w-full text-sm text-content-muted">
                <x-form.error name="photo_path" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form.label for="name" required>Name</x-form.label>
                    <x-form.input type="text" name="name" id="name" :value="old('name', auth()->user()->name)" required />
                </div>

                <div>
                    <x-form.label for="email" required>Email</x-form.label>
                    <x-form.input type="email" name="email" id="email" :value="old('email', auth()->user()->email)" required />
                </div>

                <div>
                    <x-form.label for="phone">Phone</x-form.label>
                    <x-form.input type="tel" name="phone" id="phone" :value="old('phone', auth()->user()->phone)" />
                </div>

                <div>
                    <x-form.label for="emergency_contact_phone">Emergency contact phone</x-form.label>
                    <x-form.input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                  :value="old('emergency_contact_phone', $member->emergency_contact_phone)" />
                </div>

                <div>
                    <x-form.label for="emergency_contact_name">Emergency contact name</x-form.label>
                    <x-form.input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                  :value="old('emergency_contact_name', $member->emergency_contact_name)" />
                </div>
            </div>

            <div>
                <x-form.label for="address_line1">Address line 1</x-form.label>
                <x-form.input type="text" name="address_line1" id="address_line1" :value="old('address_line1', $member->address_line1)" />
            </div>

            <div>
                <x-form.label for="address_line2">Address line 2</x-form.label>
                <x-form.input type="text" name="address_line2" id="address_line2" :value="old('address_line2', $member->address_line2)" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-form.label for="city">City</x-form.label>
                    <x-form.input type="text" name="city" id="city" :value="old('city', $member->city)" />
                </div>

                <div>
                    <x-form.label for="state">State</x-form.label>
                    <x-form.input type="text" name="state" id="state" :value="old('state', $member->state)" />
                </div>

                <div>
                    <x-form.label for="pincode">Pincode</x-form.label>
                    <x-form.input type="text" name="pincode" id="pincode" :value="old('pincode', $member->pincode)" />
                </div>
            </div>

            <p class="text-sm text-content-muted">
                Department, designation and status can only be changed by an administrator.
            </p>

            <x-button>Save changes</x-button>
        </form>
    @endif
</x-layout.portal>
