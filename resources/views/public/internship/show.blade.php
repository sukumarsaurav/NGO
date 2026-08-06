<x-layout.public title="Internship Program" description="Join our internship program and gain real-world experience in the social sector.">
    <div class="mx-auto w-full max-w-2xl">
        <h1 class="mb-2 text-center text-2xl font-bold text-content sm:text-3xl">Internship Program</h1>
        <p class="mx-auto mb-8 max-w-xl text-center text-content-muted">
            Join our team and gain real-world experience working on campaigns, community
            outreach and operations in the social sector.
        </p>

        <div class="mb-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">Hands-on work</p>
                <p class="text-sm text-content-muted">Work directly on live campaigns, outreach and field programmes, not busywork.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">Mentorship</p>
                <p class="text-sm text-content-muted">Guided by our team throughout the internship, with regular feedback.</p>
            </div>
            <div class="rounded-lg border border-line-divider bg-surface p-4 text-center">
                <p class="mb-1 font-semibold text-content">Certificate</p>
                <p class="text-sm text-content-muted">A completion certificate and letter of recommendation for strong performers.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('internship.store') }}" enctype="multipart/form-data" class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8">
            @csrf

            {{-- Honeypot — hidden from real visitors, a bot fills it. --}}
            <div style="position: absolute; left: -9999px;" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-6 space-y-4">
                <x-form.field name="name" label="Name" required autocomplete="name" />
                <x-form.field name="email" label="Email" type="email" required autocomplete="email" />
                <x-form.field name="phone" label="Phone" type="tel" required autocomplete="tel" />
                <x-form.field name="track" label="Area of interest" hint="e.g. Communications, Field Operations, Fundraising" />
                <x-form.field name="resume" label="Resume" type="file" accept=".pdf,.doc,.docx" hint="PDF or Word, max 5 MB." error-key="resume" />
                <x-form.field name="message" label="Message (optional)" type="textarea" :rows="4" />
            </div>

            <x-button size="lg" full>Submit application</x-button>
        </form>
    </div>
</x-layout.public>
