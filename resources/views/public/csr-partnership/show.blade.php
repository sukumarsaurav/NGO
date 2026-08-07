<x-layout.public title="CSR Partnership" description="Partner with us on your Corporate Social Responsibility programme.">
    <div class="mx-auto w-full max-w-2xl">
        {{-- A distinct accent icon per inquiry-form page (this one, Internship, Contact) so
             three genuinely different asks — budget, time, general inquiry — read as
             different pages, not the same template three times. See
             docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §9. --}}
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success-bg text-success-text">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
        </div>
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

        <x-flash-message />

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
