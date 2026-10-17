{{--
    The undirected-donation entry point — reached from the header's "Donate" button on
    every single page, so its traffic is real, but it used to carry none of the trust
    content a campaign page has: no impact numbers, no verification badges, barely a
    sentence of explanation before the form. See
    docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §4 — flagged there as the single
    highest-leverage gap on the site, content rather than animation.
--}}
@php
    $has80g = \App\Support\TaxExemption::is80gActive();
    $has12a = \App\Support\TaxExemption::is12aRegistered();
@endphp

<x-layout.public title="Donate" :description="$has80g ? 'Support our verified NGO campaigns in India with an instant 80G tax-exemption receipt.' : 'Support our verified NGO campaigns in India. We are a registered Section 8 non-profit company.'">
    <div class="w-full max-w-lg">
        <h1 class="mb-2 text-center text-2xl font-bold text-content">Make a donation</h1>
        <p class="mx-auto mb-6 max-w-md text-center text-content-muted">
            Every rupee here goes to wherever the need is most urgent right now — active
            campaigns first, then our emergency response fund.
            @if ($has80g)
                You'll get an instant 80G tax-exemption receipt either way.
            @else
                You'll get a donation receipt by email.
            @endif
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
                {{-- Claim exactly what the registrations on file support — a Section 8
                     company is not automatically 12A/80G registered. --}}
                @if ($has12a && $has80g)
                    12A &amp; 80G Registered
                @else
                    Registered Section 8 Company
                @endif
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

        {{-- Direct UPI payment. Settings-driven, hidden while `donation.upi_id` is empty.
             The QR is the bank's own printed card (IndusInd, payee name shown on it), so
             a donor can confirm who they are paying before they pay.

             NOTE: money arriving this way never passes through the payment gateway, so
             the app does not see it — an admin has to record it as an offline donation
             for a receipt to exist. Said here so a donor is not left waiting on one. --}}
        @php $upiId = app(\App\Services\Settings\SettingsRepository::class)->get('donation.upi_id'); @endphp
        @if ($upiId)
            <section class="mt-8 rounded-lg border border-line-divider bg-surface p-4 text-center" aria-labelledby="upi-heading">
                <h2 id="upi-heading" class="mb-1 text-base font-semibold text-content">Prefer to pay directly by UPI?</h2>
                <p class="mb-4 text-sm text-content-muted">Scan with any UPI app. The payee shows as our registered foundation.</p>
                <img
                    src="{{ asset('images/payments/upi-qr.jpg') }}"
                    alt="UPI QR code for Vision Good Work Global Foundation, IndusInd Bank"
                    width="346"
                    height="561"
                    loading="lazy"
                    decoding="async"
                    class="mx-auto h-auto w-full max-w-[16rem] rounded-md"
                >
                <p class="mt-4 text-sm text-content-muted">
                    UPI ID: <span class="select-all font-mono font-semibold text-content">{{ $upiId }}</span>
                </p>
                <p class="mt-2 text-xs text-content-muted">
                    After paying, email your name and the transaction reference to
                    {{ app(\App\Services\Settings\SettingsRepository::class)->get('org.email') ?: 'us' }}
                    so we can send you a receipt.
                </p>
            </section>
        @endif
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</x-layout.public>
