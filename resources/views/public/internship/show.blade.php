<x-layout.public title="Internship Program" description="Join our internship program and gain real-world experience in the social sector.">
    <div class="mx-auto w-full max-w-2xl">
        {{-- See the matching comment on csr-partnership/show.blade.php — same pattern, a
             different icon per inquiry-form page. --}}
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success-bg text-success-text">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M4.26 10.147a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M4.26 10.147A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
            </svg>
        </div>
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

        <x-flash-message />

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
