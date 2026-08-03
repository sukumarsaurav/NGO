<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\DTOs\MandateRequest;
use App\Services\Payment\DTOs\MandateResult;
use App\Services\Payment\DTOs\OrderResult;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\RefundResult;
use App\Services\Payment\DTOs\WebhookEvent;
use App\Support\Money;

/**
 * Deterministic, no network — what the entire donation flow is tested
 * against. See docs/modules/M05-donations-payments.md: "This costs about a
 * day and means the entire test suite runs offline."
 *
 * Every method is scriptable: call `succeedNextPayment()` /
 * `failNextPayment()` / `queueWebhook()` before exercising the code under
 * test, then assert on what comes back. Nothing here talks to Razorpay.
 */
final class FakeGateway implements PaymentGateway
{
    private int $orderSequence = 0;

    private int $paymentSequence = 0;

    /** @var array<string, PaymentResult> */
    private array $payments = [];

    /** @var array<string, string> */
    private array $mandateStatuses = [];

    private ?bool $forcedSignatureResult = null;

    public function createOrder(PaymentIntent $intent): OrderResult
    {
        $this->orderSequence++;
        // uniqid(), not just the sequence — this class is a fresh instance
        // every request (see PaymentServiceProvider), so a bare in-process
        // counter collides with itself across separate donations/requests.
        // Real Razorpay order/subscription ids are globally unique; this
        // mirrors that property well enough to satisfy our own unique
        // constraints (e.g. subscriptions.provider_subscription_id) under
        // real multi-request local/demo use, not just single-process tests.
        $orderId = 'order_fake_'.$this->orderSequence.'_'.uniqid();

        return new OrderResult(
            orderId: $orderId,
            amount: $intent->amount,
            currency: $intent->currency,
            status: 'created',
            raw: ['id' => $orderId],
        );
    }

    public function verifyPaymentSignature(array $payload): bool
    {
        return $this->forcedSignatureResult ?? true;
    }

    public function fetchPayment(string $paymentId): PaymentResult
    {
        return $this->payments[$paymentId] ?? new PaymentResult(
            paymentId: $paymentId,
            orderId: null,
            status: 'captured',
            amount: Money::zero(),
        );
    }

    public function refund(string $paymentId, Money $amount): RefundResult
    {
        $this->paymentSequence++;

        return new RefundResult(
            refundId: "rfnd_fake_{$this->paymentSequence}",
            paymentId: $paymentId,
            amount: $amount,
            status: 'processed',
        );
    }

    public function createMandate(MandateRequest $request): MandateResult
    {
        $this->orderSequence++;

        return new MandateResult(
            subscriptionId: 'sub_fake_'.$this->orderSequence.'_'.uniqid(),
            status: 'created',
        );
    }

    public function cancelMandate(string $subscriptionId): bool
    {
        $this->mandateStatuses[$subscriptionId] = 'cancelled';

        return true;
    }

    public function pauseMandate(string $subscriptionId): bool
    {
        $this->mandateStatuses[$subscriptionId] = 'paused';

        return true;
    }

    public function resumeMandate(string $subscriptionId): bool
    {
        $this->mandateStatuses[$subscriptionId] = 'active';

        return true;
    }

    public function fetchMandate(string $subscriptionId): MandateResult
    {
        return new MandateResult(
            subscriptionId: $subscriptionId,
            status: $this->mandateStatuses[$subscriptionId] ?? 'active',
        );
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        return $this->forcedSignatureResult ?? ($signature === 'valid-test-signature');
    }

    public function parseWebhook(array $payload): WebhookEvent
    {
        $entity = $payload['payload']['payment']['entity'] ?? [];
        $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? null;

        return new WebhookEvent(
            eventId: $payload['id'] ?? 'evt_fake_'.md5(json_encode($payload)),
            eventType: $payload['event'] ?? 'payment.captured',
            orderId: $entity['order_id'] ?? null,
            paymentId: $entity['id'] ?? null,
            payload: $payload,
            subscriptionId: $subscriptionEntity['id'] ?? $entity['subscription_id'] ?? null,
        );
    }

    // --- test scripting helpers -------------------------------------------

    public function registerPaymentResult(string $paymentId, PaymentResult $result): void
    {
        $this->payments[$paymentId] = $result;
    }

    /**
     * Simulates gateway-side drift for SyncSubscriptionStatus tests — e.g.
     * the bank cancelled a mandate without a webhook ever reaching us.
     */
    public function forceMandateStatus(string $subscriptionId, string $status): void
    {
        $this->mandateStatuses[$subscriptionId] = $status;
    }

    public function forceSignatureVerification(bool $result): void
    {
        $this->forcedSignatureResult = $result;
    }

    /**
     * Builds a payload matching Razorpay's `payment.captured` /
     * `payment.failed` webhook shape closely enough for RazorpayGateway's own
     * `parseWebhook()` in RazorpayGateway-specific tests, and for building
     * fixtures against FakeGateway's own `parseWebhook()`.
     *
     * @return array<string, mixed>
     */
    public static function webhookPayload(
        string $eventType,
        string $paymentId,
        string $orderId,
        int $amountPaise,
        int $feePaise = 0,
        int $taxPaise = 0,
        ?string $errorCode = null,
        ?string $errorDescription = null,
    ): array {
        return [
            'id' => 'evt_'.$paymentId,
            'event' => $eventType,
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'order_id' => $orderId,
                        'amount' => $amountPaise,
                        'fee' => $feePaise,
                        'tax' => $taxPaise,
                        'status' => $eventType === 'payment.captured' ? 'captured' : 'failed',
                        'method' => 'upi',
                        'error_code' => $errorCode,
                        'error_description' => $errorDescription,
                        'created_at' => now()->timestamp,
                        'captured' => $eventType === 'payment.captured',
                    ],
                ],
            ],
        ];
    }

    /**
     * Builds a payload matching Razorpay's `subscription.*` webhook shape —
     * `subscription.activated`, `.charged`, `.pending`, `.halted`,
     * `.cancelled`. See docs/modules/M06-recurring-autopay.md.
     *
     * @return array<string, mixed>
     */
    public static function subscriptionWebhookPayload(
        string $eventType,
        string $subscriptionId,
        string $status,
        ?string $paymentId = null,
        int $amountPaise = 0,
        ?string $errorDescription = null,
    ): array {
        $payload = [
            'subscription' => [
                'entity' => [
                    'id' => $subscriptionId,
                    'status' => $status,
                ],
            ],
        ];

        if ($paymentId) {
            $payload['payment'] = [
                'entity' => [
                    'id' => $paymentId,
                    'subscription_id' => $subscriptionId,
                    'amount' => $amountPaise,
                    'status' => $eventType === 'subscription.charged' ? 'captured' : 'failed',
                    'method' => 'upi',
                    'error_description' => $errorDescription,
                    'created_at' => now()->timestamp,
                    'captured' => $eventType === 'subscription.charged',
                ],
            ];
        }

        return [
            'id' => 'evt_'.$subscriptionId.'_'.$eventType,
            'event' => $eventType,
            'payload' => $payload,
        ];
    }
}
