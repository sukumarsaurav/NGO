@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
@endphp

<x-layout.public title="Contact Us" description="Get in touch with us — questions, partnerships, or feedback.">
    <div class="w-full max-w-lg">
        {{-- See the matching comment on csr-partnership/show.blade.php — same pattern, a
             different icon per inquiry-form page. --}}
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success-bg text-success-text">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>
        <h1 class="mb-2 text-center text-2xl font-bold text-content">Contact Us</h1>
        <p class="mb-6 text-center text-sm text-content-muted">We'd love to hear from you.</p>

        <x-flash-message />

        <form method="POST" action="{{ route('contact.store') }}" class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8">
            @csrf

            {{-- Honeypot — hidden from real visitors, a bot fills it. Inline
                 style rather than a Tailwind arbitrary-value class, so it
                 can't silently stop working if the class is ever purged. --}}
            <div style="position: absolute; left: -9999px;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-6 space-y-4">
                <x-form.field name="name" label="Name" required autocomplete="name" />
                <x-form.field name="email" label="Email" type="email" required autocomplete="email" />
                <x-form.field name="phone" label="Phone" type="tel" autocomplete="tel" />
                <x-form.field name="subject" label="Subject" />
                <x-form.field name="message" label="Message" type="textarea" :rows="5" required />
            </div>

            <x-button size="lg" full>Send message</x-button>
        </form>

        @if ($settings->get('org.email') || $settings->get('org.phone'))
            <div class="mt-6 text-center text-sm text-content-muted">
                @if ($settings->get('org.email'))
                    <p>Email: <a href="mailto:{{ $settings->get('org.email') }}" class="text-link hover:text-link-hover">{{ $settings->get('org.email') }}</a></p>
                @endif
                @if ($settings->get('org.phone'))
                    <p>Phone: {{ $settings->get('org.phone') }}</p>
                @endif
            </div>
        @endif
    </div>

    <script type="application/ld+json">
        {!! json_encode(['@context' => 'https://schema.org', '@type' => 'ContactPage', 'url' => url()->current()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
</x-layout.public>
