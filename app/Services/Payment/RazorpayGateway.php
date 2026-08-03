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
use DateTimeImmutable;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Order;
use Razorpay\Api\Payment;
use Razorpay\Api\Subscription;
use Razorpay\Api\Utility;

/**
 * Production PaymentGateway implementation. See
 * docs/modules/M05-donations-payments.md's "gateway abstraction" section —
 * every method here mirrors the interface 1:1; nothing calling code needs to
 * know is Razorpay-specific.
 */
final class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        string $key,
        string $secret,
        private readonly string $webhookSecret,
    ) {
        // Razorpay\Api\Entity subclasses (Order, Payment, ...) read their
        // credentials from Api's static state, not from an instance passed
        // around — constructing this once is what makes `new Order()` etc.
        // authenticated below.
        new Api($key, $secret);
    }

    public function createOrder(PaymentIntent $intent): OrderResult
    {
        $order = (new Order)->create([
            'receipt' => $intent->receipt,
            'amount' => $intent->amount->toPaise(),
            'currency' => $intent->currency,
            'notes' => $intent->notes,
        ]);

        $attributes = $order->toArray();

        return new OrderResult(
            orderId: $attributes['id'],
            amount: Money::fromPaise((int) $attributes['amount']),
            currency: $attributes['currency'],
            status: $attributes['status'],
            raw: $attributes,
        );
    }

    public function verifyPaymentSignature(array $payload): bool
    {
        try {
            (new Utility)->verifyPaymentSignature($payload);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function fetchPayment(string $paymentId): PaymentResult
    {
        $payment = (new Payment)->fetch($paymentId)->toArray();

        return $this->paymentResultFromAttributes($payment);
    }

    public function refund(string $paymentId, Money $amount): RefundResult
    {
        $refund = (new Payment)->fetch($paymentId)->refund([
            'amount' => $amount->toPaise(),
        ]);

        $attributes = $refund->toArray();

        return new RefundResult(
            refundId: $attributes['id'],
            paymentId: $attributes['payment_id'],
            amount: Money::fromPaise((int) $attributes['amount']),
            status: $attributes['status'] ?? 'processed',
            raw: $attributes,
        );
    }

    public function createMandate(MandateRequest $request): MandateResult
    {
        $subscription = (new Subscription)->create([
            'plan_id' => $request->frequency,
            'total_count' => $request->totalCount,
            'notes' => [
                'donor_email' => $request->donorEmail,
                'donor_name' => $request->donorName,
            ],
        ]);

        $attributes = $subscription->toArray();

        return new MandateResult(
            subscriptionId: $attributes['id'],
            status: $attributes['status'],
            shortUrl: $attributes['short_url'] ?? null,
            raw: $attributes,
        );
    }

    public function cancelMandate(string $subscriptionId): bool
    {
        $result = (new Subscription)->fetch($subscriptionId)->cancel();

        return ($result->toArray()['status'] ?? null) === 'cancelled';
    }

    public function pauseMandate(string $subscriptionId): bool
    {
        $result = (new Subscription)->fetch($subscriptionId)->pause(['pause_at' => 'now']);

        return ($result->toArray()['status'] ?? null) === 'paused';
    }

    public function resumeMandate(string $subscriptionId): bool
    {
        $result = (new Subscription)->fetch($subscriptionId)->resume(['resume_at' => 'now']);

        return ($result->toArray()['status'] ?? null) === 'active';
    }

    public function fetchMandate(string $subscriptionId): MandateResult
    {
        $attributes = (new Subscription)->fetch($subscriptionId)->toArray();

        return new MandateResult(
            subscriptionId: $attributes['id'],
            status: $attributes['status'],
            shortUrl: $attributes['short_url'] ?? null,
            raw: $attributes,
        );
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        try {
            (new Utility)->verifyWebhookSignature($body, $signature, $this->webhookSecret);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function parseWebhook(array $payload): WebhookEvent
    {
        $entity = $payload['payload']['payment']['entity']
            ?? $payload['payload']['order']['entity']
            ?? [];

        $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? null;

        return new WebhookEvent(
            eventId: $payload['id'] ?? ($entity['id'] ?? $subscriptionEntity['id'] ?? '').'-'.($payload['event'] ?? 'unknown'),
            eventType: $payload['event'] ?? 'unknown',
            orderId: $entity['order_id'] ?? null,
            paymentId: $entity['id'] ?? null,
            payload: $payload,
            subscriptionId: $subscriptionEntity['id'] ?? $entity['subscription_id'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function paymentResultFromAttributes(array $attributes): PaymentResult
    {
        return new PaymentResult(
            paymentId: $attributes['id'],
            orderId: $attributes['order_id'] ?? null,
            status: $attributes['status'],
            amount: Money::fromPaise((int) $attributes['amount']),
            fee: Money::fromPaise((int) ($attributes['fee'] ?? 0)),
            tax: Money::fromPaise((int) ($attributes['tax'] ?? 0)),
            method: $attributes['method'] ?? null,
            bank: $attributes['bank'] ?? null,
            vpa: $attributes['vpa'] ?? null,
            cardLast4: $attributes['card']['last4'] ?? null,
            errorCode: $attributes['error_code'] ?? null,
            errorDescription: $attributes['error_description'] ?? null,
            capturedAt: isset($attributes['captured']) && $attributes['captured'] && isset($attributes['created_at'])
                ? (new DateTimeImmutable)->setTimestamp((int) $attributes['created_at'])
                : null,
            raw: $attributes,
        );
    }
}
