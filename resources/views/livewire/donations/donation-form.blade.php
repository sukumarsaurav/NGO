<div
    x-data="{ submitting: false, checkoutError: false }"
    x-on:donation-initiated.window="submitting = true; checkoutError = false"
    x-on:checkout-unavailable.window="checkoutError = true; submitting = false"
    class="mx-auto max-w-lg rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-8"
>
    @if ($step === 1)
        @if ($selectedItems !== [])
            <div class="mb-6 rounded-md bg-background p-3 text-sm">
                <p class="mb-1 font-medium text-content">Your selection</p>
                @foreach ($selectedItems as $item)
                    <div class="flex justify-between text-content-muted">
                        <span>{{ $item['quantity'] }} &times; {{ optional(\App\Models\CampaignProduct::find($item['product_id']))->name ?? 'Item' }}</span>
                        <span>&#8377;{{ number_format($item['quantity'] * $item['expected_unit_price'] / 100) }}</span>
                    </div>
                @endforeach
                <div class="mt-1 flex justify-between border-t border-line-divider pt-1 font-medium text-content">
                    <span>Catalogue subtotal</span>
                    <span>&#8377;{{ number_format($this->itemsAmountPaise() / 100) }}</span>
                </div>
            </div>
        @endif

        <div class="mb-6">
            {{-- A radiogroup, not a row of buttons: the presets are one choice with one answer,
                 which is what `role="radio"` + `aria-checked` conveys and a plain button row
                 does not. --}}
            <p id="amount-presets-label" class="mb-3 block text-sm font-semibold text-content">Select Donation Amount</p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" role="radiogroup" aria-labelledby="amount-presets-label">
                @foreach ($this->presets() as $presetPaise)
                    @php
                        $presetVal = (int) ($presetPaise / 100);
                        $presetTag = match(true) {
                            $presetVal <= 500 => '1 Week Food',
                            $presetVal <= 1000 => 'Shelter & Kit',
                            $presetVal <= 2500 => '1 Month Medical',
                            default => 'Critical Care',
                        };
                        $isPopular = $presetVal === 1000;
                    @endphp
                    <button
                        type="button"
                        role="radio"
                        aria-checked="{{ $selectedPreset === $presetPaise ? 'true' : 'false' }}"
                        wire:click="selectPreset({{ $presetPaise }})"
                        class="relative flex flex-col items-center justify-center rounded-lg border p-3 transition-all duration-fast text-center {{ $selectedPreset === $presetPaise ? 'border-brand-700 bg-brand-50/80 text-brand-900 ring-2 ring-brand-600/30' : 'border-line-divider bg-surface text-content hover:border-brand-300 hover:bg-surface-muted' }}"
                    >
                        @if ($isPopular)
                            <span class="absolute -top-2.5 rounded-full bg-brand-800 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-white shadow-xs">Popular</span>
                        @endif
                        <span class="font-heading text-lg font-bold">₹{{ number_format($presetVal) }}</span>
                        <span class="text-[0.7rem] text-content-muted mt-0.5 line-clamp-1">{{ $presetTag }}</span>
                    </button>
                @endforeach
            </div>

            {{-- `.live.debounce` so the submit button's amount tracks what has been typed. --}}
            <x-form.field
                class="mt-3"
                name="amount"
                label="Other amount (₹)"
                type="number"
                min="1"
                inputmode="numeric"
                wire:model.live.debounce.400ms="amount"
            />

            @if ($amount && (float) $amount > 0)
                <div class="mt-3 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50/80 p-2.5 text-xs text-emerald-900">
                    <svg class="h-4 w-4 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><strong>80G Tax Savings:</strong> Save approx. <strong>₹{{ number_format((float)$amount * 0.5) }}</strong> in income tax (50% deduction under Sec 80G).</span>
                </div>
            @endif
        </div>

        <div class="mb-6">
            <x-form.checkbox name="wantsMonthly" wire:model.live="wantsMonthly" :checked="$wantsMonthly">
                Make this a monthly donation
            </x-form.checkbox>
            @if ($wantsMonthly)
                <p class="ml-6 text-sm text-content-muted">
                    &#8377;{{ number_format((float) $amount) }} every month via UPI Autopay &middot; cancel anytime
                </p>
            @endif
        </div>

        <x-button type="button" size="xl" full wire:click="nextStep">
            Continue
        </x-button>
    @else
        <button type="button" wire:click="previousStep" class="mb-4 flex min-h-touch items-center gap-1 text-sm font-semibold text-content-muted hover:text-content">
            &larr; Back
        </button>

        {{-- Shown when Razorpay's script fails to load. This used to be a browser `alert()`
             fired at the exact moment of payment — a modal the donor has to dismiss before
             they can even see the form their details are still sitting in. --}}
        <div x-show="checkoutError" x-cloak class="mb-4">
            <x-alert variant="danger" title="Secure checkout could not load">
                Your details are saved. Please check your connection and try again, or contact us
                and we will complete the donation with you.
            </x-alert>
        </div>

        @if ($errors->has('conflict'))
            <x-alert variant="danger" class="mb-4">{{ $errors->first('conflict') }}</x-alert>
        @endif

        @if ($droppedProductNames !== [])
            <x-alert variant="warning" class="mb-4" title="Some items were removed">
                {{ implode(', ', $droppedProductNames) }} {{ count($droppedProductNames) === 1 ? 'was' : 'were' }}
                no longer available and {{ count($droppedProductNames) === 1 ? 'was' : 'were' }} removed from your order.
                The rest of your donation went through.
            </x-alert>
        @endif

        @if ($priceChangeConfirmationNeeded)
            <x-alert variant="warning" class="mb-4" title="The price changed">
                Your new total is
                <strong>&#8377;{{ number_format($priceChangeNewTotalPaise / 100, 2) }}</strong>
                (was &#8377;{{ number_format($priceChangeOldTotalPaise / 100, 2) }}).
                <button type="button" wire:click="donate" class="mt-2 block min-h-touch font-semibold underline">
                    Continue with the new total
                </button>
            </x-alert>
        @endif

        {{-- Stacked, not `sm:grid-cols-3`. This card sits in a ~380px sidebar on desktop, but
             `sm:` keys off the viewport, so the three fields collapsed to roughly 60px wide
             each on exactly the screens with the most room to spare. --}}
        <div class="mb-4 space-y-4">
            <x-form.field name="name" label="Name" required wire:model="name" autocomplete="name" />
            <x-form.field name="email" label="Email" type="email" required wire:model="email" autocomplete="email" />
            <x-form.field name="phone" label="Phone" type="tel" required wire:model="phone" autocomplete="tel" />
        </div>

        <div class="mb-4">
            <x-form.checkbox name="want80g" wire:model.live="want80g" :checked="$want80g">
                I want an 80G tax-exemption receipt
            </x-form.checkbox>

            @if ($want80g)
                <div class="mt-3 space-y-4 rounded-md bg-background p-3">
                    <x-form.field name="pan" label="PAN" required wire:model="pan" class="uppercase" autocomplete="off" />
                    <x-form.field name="addressLine1" label="Address" required placeholder="Address line 1" wire:model="addressLine1" autocomplete="address-line1" />
                    <x-form.field name="addressLine2" label="Address line 2" placeholder="Optional" wire:model="addressLine2" autocomplete="address-line2" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.field name="city" label="City" required wire:model="city" autocomplete="address-level2" />
                        <x-form.field name="state" label="State" required wire:model="state" autocomplete="address-level1" />
                    </div>
                    <x-form.field name="pincode" label="Pincode" required wire:model="pincode" autocomplete="postal-code" inputmode="numeric" />
                </div>
            @endif
        </div>

        <div class="mb-4">
            <x-form.checkbox name="isAnonymous" wire:model.live="isAnonymous" :checked="$isAnonymous">
                Donate anonymously
            </x-form.checkbox>

            @if ($this->conflictsWith80gAndAnonymous())
                <x-alert variant="warning" class="mt-2">
                    An 80G receipt must carry your name and PAN, so it can't be issued for an anonymous donation.
                    Uncheck one to continue.
                </x-alert>
            @endif
        </div>

        <div class="mb-4">
            <x-form.field name="message" label="Message (optional)" type="textarea" :rows="2" wire:model="message" />
        </div>

        <div class="mb-6">
            <x-form.checkbox name="acceptedTerms" wire:model="acceptedTerms" :checked="$acceptedTerms">
                I accept the Terms &amp; Conditions
            </x-form.checkbox>
        </div>

        {{-- `xl` is the size §10.1 reserves for the donate CTA. `aria-busy` and the spinner give
             the wait a visible and an announced form — the button previously swapped its label
             silently. --}}
        <x-button
            type="button"
            size="xl"
            full
            wire:click="donate"
            wire:loading.attr="disabled"
            x-bind:disabled="submitting"
            x-bind:aria-busy="submitting ? 'true' : 'false'"
        >
            <span wire:loading.remove wire:target="donate" x-show="!submitting">
                @if ($wantsMonthly)
                    Set up &#8377;{{ number_format((float) $amount) }}/month
                @else
                    Donate &#8377;{{ number_format($this->totalPaise() / 100) }}
                @endif
            </span>
            <span wire:loading wire:target="donate" class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z" />
                </svg>
                Processing…
            </span>
            <span x-show="submitting" x-cloak>Opening secure checkout…</span>
        </x-button>

        <p class="mt-3 text-center text-xs text-content-muted">
            &#128274; Secure payment &middot; 80G eligible &middot; UPI &middot; Cards &middot; Net Banking
        </p>
    @endif
</div>

@script
<script>
    let checkout = null;

    Livewire.on('donation-initiated', (event) => {
        const data = Array.isArray(event) ? event[0] : event;

        if (data.isFake) {
            // No real Razorpay keys configured yet — PaymentServiceProvider
            // fell back to FakeGateway server-side. There is no real
            // Checkout.js modal to open (it can't authenticate against
            // Razorpay without a real key either), so simulate the success
            // callback directly. Swap in real keys later and this branch
            // stops firing automatically — see PaymentServiceProvider.
            setTimeout(() => {
                $wire.handlePaymentSuccess({
                    razorpay_payment_id: 'pay_demo_' + Date.now(),
                    razorpay_order_id: data.orderId,
                    razorpay_signature: 'demo-signature',
                });
            }, 1200);
            return;
        }

        if (typeof Razorpay === 'undefined') {
            // Surface it in the page rather than in a browser dialog — see the alert at the
            // top of this component, which listens for this event. It also clears
            // `submitting`, or the button stays stuck on "Opening secure checkout…" for a
            // checkout that will never open.
            window.dispatchEvent(new CustomEvent('checkout-unavailable'));
            $wire.set('pendingOrderId', null);
            return;
        }

        const options = {
            key: data.razorpayKey,
            amount: data.amountPaise,
            currency: data.currency,
            order_id: data.orderId,
            name: @js(config('app.name')),
            prefill: { name: data.name, email: data.email, contact: data.phone },
            handler: function (response) {
                $wire.handlePaymentSuccess(response);
            },
            modal: {
                ondismiss: function () {
                    // Case A — dismissed without paying. Stay on the page,
                    // fields stay filled, neutral message. See
                    // docs/06-UI-UX-FOUNDATION.md §6.
                    $wire.set('pendingOrderId', null);
                },
            },
        };

        checkout = new Razorpay(options);
        checkout.on('payment.failed', function (response) {
            $wire.handlePaymentFailed(
                response.error?.code ?? null,
                response.error?.description ?? null,
            );
        });
        checkout.open();
    });
</script>
@endscript
