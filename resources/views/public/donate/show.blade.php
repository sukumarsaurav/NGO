{{--
    The undirected-donation entry point — reached from the header's "Donate" button on
    every single page, so its traffic is real, but it used to carry none of the trust
    content a campaign page has: no impact numbers, no verification badges, barely a
    sentence of explanation before the form. See
    docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §4 — flagged there as the single
    highest-leverage gap on the site, content rather than animation.
--}}
<x-layout.public title="Donate" description="Support our verified NGO campaigns in India with an instant 80G tax-exemption receipt.">
    <div class="w-full max-w-lg">
        <h1 class="mb-2 text-center text-2xl font-bold text-content">Make a donation</h1>
        <p class="mx-auto mb-6 max-w-md text-center text-content-muted">
            Every rupee here goes to wherever the need is most urgent right now — active
            campaigns first, then our emergency response fund. You'll get an instant 80G
            tax-exemption receipt either way.
        </p>

        {{-- Same trust signals a campaign page's donation card leads with, static here
             rather than the clickable modal version — this page isn't tied to one
             campaign's specific credentials, just the organisation's own. --}}
        <div class="mb-6 flex flex-wrap justify-center gap-2">
            <x-badge variant="trust">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Verified NGO
            </x-badge>
            <x-badge variant="trust">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                12A &amp; 80G Registered
            </x-badge>
            <x-badge variant="trust">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                100% Fund Transparency
            </x-badge>
        </div>

        <x-impact-stats :stats="$impactStats" class="mb-8" />

        @livewire('donations.donation-form')
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</x-layout.public>
