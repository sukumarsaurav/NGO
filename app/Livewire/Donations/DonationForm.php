<?php

declare(strict_types=1);

namespace App\Livewire\Donations;

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordFailedDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Subscriptions\CreateSubscriptionMandate;
use App\Enums\MandateType;
use App\Models\CampaignProduct;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

/**
 * The one-step donation form — built once, rendered on both `/donate` and
 * (later, M08) the campaign-page checkout modal. See
 * docs/06-UI-UX-FOUNDATION.md §5, which supersedes the field list in
 * M05's own UI section — this class follows that spec, not the older one.
 *
 * No separate StoreDonationRequest: Livewire validates its own properties
 * (the equivalent ruleset), then InitiateDonation re-validates the amount
 * server-side regardless — client-side UX is never the last word on money.
 */
class DonationForm extends Component
{
    public ?int $campaignId = null;

    /**
     * Purely presentational — which half of the form is currently shown.
     * `donate()` never checks this; it re-validates everything itself
     * regardless of what step the UI thinks it's on.
     */
    public int $step = 1;

    public string $amount = '1000';

    public ?int $selectedPreset = null;

    /**
     * Always shown on the general /donate page per docs/06-UI-UX-FOUNDATION.md
     * §5; on a campaign page it's conditional on the campaign allowing
     * recurring gifts — that gating arrives with M08 (campaigns).
     */
    public bool $wantsMonthly = false;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|email|max:190')]
    public string $email = '';

    #[Validate('required|string|max:20')]
    public string $phone = '';

    public bool $want80g = true;

    public string $pan = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $state = '';

    public string $pincode = '';

    public bool $isAnonymous = false;

    public string $message = '';

    public bool $acceptedTerms = false;

    /** @var array<string, string> */
    public array $utmData = [];

    public ?string $pendingOrderId = null;

    public ?string $pendingDonationUuid = null;

    /**
     * Synced from the sibling `ProductCatalogue` component via the
     * `catalogue-updated` browser event — see that component's docblock for
     * why this is event-based rather than a nested prop. Each entry also
     * carries the unit price the catalogue displayed at selection time, so
     * `donate()` can detect a price change since page load.
     *
     * @var list<array{product_id: int, quantity: int, expected_unit_price: int}>
     */
    public array $selectedItems = [];

    /** @var list<string> */
    public array $droppedProductNames = [];

    public bool $priceChangeConfirmationNeeded = false;

    public ?int $priceChangeOldTotalPaise = null;

    public ?int $priceChangeNewTotalPaise = null;

    public function mount(?int $campaignId = null): void
    {
        $this->campaignId = $campaignId;
        $this->utmData = collect(request()->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content']))
            ->filter()
            ->all();

        // `$amount` defaults to '1000' and `$selectedPreset` defaults to null — without
        // this, the form opened showing an amount with no chip selected, two controls
        // disagreeing about the same value. See
        // docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.1 and
        // docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md PR 2.1 item 6.
        $amountPaise = Money::fromRupees($this->amount)->toPaise();
        if (in_array($amountPaise, $this->presets(), true)) {
            $this->selectedPreset = $amountPaise;
        }
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    #[On('catalogue-updated')]
    public function syncSelectedItems(array $items): void
    {
        $prices = CampaignProduct::query()
            ->whereIn('id', array_column($items, 'product_id'))
            ->pluck('unit_price', 'id');

        $this->selectedItems = array_map(
            fn (array $item) => [...$item, 'expected_unit_price' => (int) ($prices[$item['product_id']] ?? 0)],
            $items
        );

        // A fresh selection supersedes any stale price-change prompt from a
        // previous submit attempt.
        $this->priceChangeConfirmationNeeded = false;
    }

    public function itemsAmountPaise(): int
    {
        return array_sum(array_map(
            fn (array $item) => $item['quantity'] * $item['expected_unit_price'],
            $this->selectedItems
        ));
    }

    public function totalPaise(): int
    {
        return $this->itemsAmountPaise() + Money::fromRupees($this->amount ?: '0')->toPaise();
    }

    /**
     * Deliberately resolves the container itself rather than taking
     * SettingsRepository as a parameter — Livewire only injects into
     * lifecycle hooks and wire:click-bound action methods, not into plain
     * methods called from within the Blade view like `$this->presets()`.
     *
     * @return array<int, int>
     */
    public function presets(): array
    {
        /** @var array<int, int> $presets */
        $presets = app(SettingsRepository::class)->get('donation.preset_amounts', [50000, 100000, 250000, 500000]);

        return $presets;
    }

    public function selectPreset(int $paise): void
    {
        $this->selectedPreset = $paise;
        $this->amount = (string) ($paise / 100);
    }

    /**
     * Typing a custom amount clears whichever preset chip is highlighted — the chip and the
     * field must never both look authoritative.
     *
     * Livewire fires `updated*` hooks only for client-side changes, so selectPreset() above
     * can assign $amount without this undoing it. The view previously tried to do this with
     * `wire:click="$set('selectedPreset', null)"` on the input, which cost a network round
     * trip on every focus and never fired when the user actually typed.
     */
    public function updatedAmount(): void
    {
        $this->selectedPreset = null;
    }

    public function nextStep(): void
    {
        $this->validateOnly('amount');
        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    public function conflictsWith80gAndAnonymous(): bool
    {
        return $this->want80g && $this->isAnonymous;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:20'],
            // A pure-catalogue donation (no free-form top-up) is valid — see
            // docs/modules/M08-campaigns-crowdfunding.md's "money rules".
            // Only the general /donate page (no catalogue selection) needs a
            // strictly positive free amount.
            'amount' => $this->selectedItems !== []
                ? ['required', 'numeric', 'min:0']
                : ['required', 'numeric', 'min:1'],
            'acceptedTerms' => ['accepted'],
        ];

        if ($this->want80g) {
            $rules['pan'] = ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i'];
            $rules['addressLine1'] = ['required', 'string', 'max:190'];
            $rules['city'] = ['required', 'string', 'max:80'];
            $rules['state'] = ['required', 'string', 'max:80'];
            $rules['pincode'] = ['required', 'string', 'max:10'];
        }

        return $rules;
    }

    /**
     * The submission itself never runs through a named route (it's a
     * Livewire AJAX call), so route-level `throttle:` middleware never
     * touches it — rate limiting has to live here instead. See
     * docs/03-ROADMAP.md's Sprint 15 acceptance criterion: "Rate limiting
     * blocks a 100-requests-per-minute donation flood."
     */
    public function donate(InitiateDonation $initiateDonation, PaymentGateway $gateway): void
    {
        $rateLimitKey = 'donate:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $this->addError('rateLimit', 'Too many attempts — please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        if ($this->conflictsWith80gAndAnonymous()) {
            $this->addError('conflict', "An 80G receipt must carry your name and PAN, so it can't be issued for an anonymous donation. Uncheck one to continue.");

            return;
        }

        $this->validate();

        // Product price changed since the donor last saw the catalogue (an
        // admin edited it mid-session) — the server price will win regardless
        // once we call InitiateDonation, but M08 requires showing the donor
        // the new total and getting an explicit confirmation before the
        // gateway opens, rather than silently charging a different amount
        // than what's on screen.
        if ($this->selectedItems !== [] && ! $this->priceChangeConfirmationNeeded) {
            $currentPrices = CampaignProduct::query()
                ->whereIn('id', array_column($this->selectedItems, 'product_id'))
                ->pluck('unit_price', 'id');

            $oldItemsTotal = $this->itemsAmountPaise();
            $newItemsTotal = 0;
            $changed = false;

            foreach ($this->selectedItems as &$item) {
                $currentPrice = (int) ($currentPrices[$item['product_id']] ?? $item['expected_unit_price']);

                if ($currentPrice !== $item['expected_unit_price']) {
                    $changed = true;
                }

                $item['expected_unit_price'] = $currentPrice;
                $newItemsTotal += $currentPrice * $item['quantity'];
            }
            unset($item);

            if ($changed) {
                $freeAmountPaise = Money::fromRupees($this->amount ?: '0')->toPaise();
                $this->priceChangeConfirmationNeeded = true;
                $this->priceChangeOldTotalPaise = $oldItemsTotal + $freeAmountPaise;
                $this->priceChangeNewTotalPaise = $newItemsTotal + $freeAmountPaise;

                return;
            }
        }

        $this->priceChangeConfirmationNeeded = false;

        $amount = Money::fromRupees($this->amount);

        if ($this->wantsMonthly) {
            $this->donateMonthly($amount);

            return;
        }

        $items = array_map(
            fn (array $item) => ['product_id' => $item['product_id'], 'quantity' => $item['quantity']],
            $this->selectedItems
        );

        try {
            $result = $initiateDonation->handle(
                donorName: $this->name,
                donorEmail: $this->email,
                donorPhone: $this->phone,
                amount: $amount,
                campaignId: $this->campaignId,
                isAnonymous: $this->isAnonymous,
                message: $this->message ?: null,
                source: 'website',
                utmData: $this->utmData,
                ipAddress: request()->ip(),
                items: $items,
            );
        } catch (InvalidArgumentException $e) {
            // The client-submitted amount is a suggestion, never a fact — see
            // docs/modules/M05-donations-payments.md's server-side validation
            // rule. Livewire's own `min:1` rule catches the obvious cases;
            // this is the second line of defence against the settings floor.
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->droppedProductNames = $result->droppedProductNames;

        if ($this->want80g) {
            $result->donation->donor->update([
                'pan' => $this->pan,
                'address_line1' => $this->addressLine1,
                'address_line2' => $this->addressLine2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
            ]);
        }

        $this->pendingOrderId = $result->order->orderId;
        $this->pendingDonationUuid = (string) $result->donation->uuid;

        $this->dispatch('donation-initiated', [
            'orderId' => $result->order->orderId,
            'amountPaise' => $result->donation->amount,
            'currency' => $result->donation->currency,
            'razorpayKey' => (string) Config::get('services.razorpay.key'),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'donationUuid' => $this->pendingDonationUuid,
            // No real Checkout.js modal without real Razorpay keys — see
            // PaymentServiceProvider's fallback rule. The JS side simulates
            // a successful payment instead of opening a modal it can't
            // actually authenticate, so the full flow is still watchable
            // end-to-end before real credentials exist.
            'isFake' => $gateway instanceof FakeGateway,
        ]);
    }

    /**
     * The mandate setup flow is a full navigation, not a modal — see
     * docs/06-UI-UX-FOUNDATION.md §6's "Recurring mandates are a different
     * flow entirely". No Checkout.js here; the donor leaves the site for
     * Razorpay's authentication page and returns by redirect.
     */
    private function donateMonthly(Money $amount): void
    {
        try {
            $subscription = app(CreateSubscriptionMandate::class)->handle(
                donorName: $this->name,
                donorEmail: $this->email,
                donorPhone: $this->phone,
                amount: $amount,
                mandateType: MandateType::UpiAutopay,
                campaignId: $this->campaignId,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        if ($this->want80g) {
            $subscription->donor->update([
                'pan' => $this->pan,
                'address_line1' => $this->addressLine1,
                'address_line2' => $this->addressLine2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
            ]);
        }

        $this->redirectRoute('donate.monthly.redirecting', ['uuid' => $subscription->uuid]);
    }

    /**
     * Bridges Razorpay Checkout.js's success handler back to the server —
     * the Livewire-native equivalent of "POST /donate/callback" in
     * docs/modules/M05-donations-payments.md's flow diagram. Whichever of
     * this and the webhook arrives first wins; RecordSuccessfulDonation is
     * idempotent either way.
     */
    public function handlePaymentSuccess(
        array $response,
        PaymentGateway $gateway,
        RecordSuccessfulDonation $recordSuccess,
    ): void {
        if (! $gateway->verifyPaymentSignature($response)) {
            $this->redirectRoute('donate.failed', ['donation' => $this->pendingDonationUuid]);

            return;
        }

        $paymentId = (string) $response['razorpay_payment_id'];

        if ($gateway instanceof FakeGateway) {
            // The simulated-success path has no real gateway to fetch fee/tax
            // from — seed a plausible result for the payment id the JS side
            // made up, so the receipt shows the real donated amount instead
            // of FakeGateway's zero-amount default.
            $gateway->registerPaymentResult($paymentId, new PaymentResult(
                paymentId: $paymentId,
                orderId: (string) $response['razorpay_order_id'],
                status: 'captured',
                amount: Money::fromRupees($this->amount),
                method: 'upi',
            ));
        }

        $paymentResult = $gateway->fetchPayment($paymentId);

        try {
            $donation = $recordSuccess->handle((string) $response['razorpay_order_id'], $paymentResult);
            $this->redirectRoute('donate.success', ['donation' => $donation->uuid]);
        } catch (Throwable) {
            // Callback raced ahead of the transaction row, or the payment
            // hasn't settled from the gateway's point of view yet — the
            // pending page polls and the webhook completes it regardless.
            $this->redirectRoute('donate.pending', ['donation' => $this->pendingDonationUuid]);
        }
    }

    public function handlePaymentFailed(?string $errorCode = null, ?string $errorDescription = null): void
    {
        if ($this->pendingOrderId) {
            try {
                app(RecordFailedDonation::class)->handle($this->pendingOrderId, new PaymentResult(
                    paymentId: 'unknown',
                    orderId: $this->pendingOrderId,
                    status: 'failed',
                    amount: Money::fromRupees($this->amount),
                    errorCode: $errorCode,
                    errorDescription: $errorDescription,
                ));
            } catch (Throwable) {
                // The webhook is the authoritative backstop; this is best-effort UX.
            }
        }

        $this->redirectRoute('donate.failed', ['donation' => $this->pendingDonationUuid]);
    }

    public function render()
    {
        return view('livewire.donations.donation-form');
    }
}
