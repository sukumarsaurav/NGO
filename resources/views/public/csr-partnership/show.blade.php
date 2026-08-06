<x-layout.public title="CSR Partnership" description="Partner with us on your Corporate Social Responsibility programme.">
    <div class="mx-auto w-full max-w-2xl">
        <h1 class="mb-2 text-center text-2xl font-bold text-content sm:text-3xl">CSR Partnership</h1>
        <p class="mx-auto mb-8 max-w-xl text-center text-content-muted">
            Partner with us to channel your organisation's CSR budget into measurable, verified
            impact.
        </p>

        <div class="mb-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">Verified impact</p>
                <p class="text-sm text-content-muted">Every campaign we run is verified, with regular updates showing exactly how funds are used.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">80G tax benefit</p>
                <p class="text-sm text-content-muted">Contributions carry a full 80G tax-exemption receipt for your organisation.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">Tailored reporting</p>
                <p class="text-sm text-content-muted">We provide impact reports suited to your CSR committee's documentation needs.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('csr-partnership.store') }}" class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8">
            @csrf

            {{-- Honeypot — hidden from real visitors, a bot fills it. --}}
            <div style="position: absolute; left: -9999px;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-6 space-y-4">
                <x-form.field name="organisation_name" label="Organisation name" required />
                <x-form.field name="contact_name" label="Contact name" required autocomplete="name" />
                <x-form.field name="email" label="Email" type="email" required autocomplete="email" />
                <x-form.field name="phone" label="Phone" type="tel" required autocomplete="tel" />
                <x-form.field name="message" label="Tell us about your CSR goals" type="textarea" :rows="5" required />
            </div>

            <x-button size="lg" full>Send inquiry</x-button>
        </form>
    </div>
</x-layout.public>
