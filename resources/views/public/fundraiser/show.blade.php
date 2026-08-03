<x-layout.public title="Start a Fundraiser">
    <div class="w-full max-w-lg">
        <h1 class="mb-2 text-center text-2xl font-bold text-content">Start a Fundraiser</h1>
        <p class="mb-6 text-center text-sm text-content-muted">
            Tell us about the cause — our team reviews every request and gets back to you.
        </p>

        @if (session('status'))
            <div class="mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('fundraiser.store') }}" enctype="multipart/form-data" class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8">
            @csrf

            <div class="mb-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field name="name" label="Your name" required autocomplete="name" />
                    <x-form.field name="organisation_name" label="Organisation" hint="Optional" autocomplete="organization" />
                    <x-form.field name="email" label="Email" type="email" required autocomplete="email" />
                    <x-form.field name="phone" label="Phone" type="tel" required autocomplete="tel" />
                </div>

                <x-form.select
                    name="cause_category_id"
                    label="Cause category"
                    placeholder="Not sure yet"
                    :options="$categories->pluck('name', 'id')->all()"
                />

                <x-form.field name="title" label="Fundraiser title" required />
                <x-form.field name="description" label="Tell us about the cause" type="textarea" :rows="5" required />
                <x-form.field name="goal_amount" label="Goal amount (₹)" type="number" min="1" inputmode="numeric" required />
                <x-form.field
                    name="documents[]"
                    label="Supporting documents"
                    type="file"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    hint="Registration certificate, 80G, photos. PDF, JPG or PNG."
                    error-key="documents.0"
                />
            </div>

            <x-button size="lg" full>
                Submit request
            </x-button>
        </form>
    </div>
</x-layout.public>
