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
 * Donation logic never touches the Razorpay SDK directly — everything goes
 * through this. See docs/modules/M05-donations-payments.md's "gateway
 * abstraction" section. Two implementations: RazorpayGateway (production)
 * and FakeGateway (tests — deterministic, no network).
 */
interface PaymentGateway
{
    public function createOrder(PaymentIntent $intent): OrderResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyPaymentSignature(array $payload): bool;

    public function fetchPayment(string $paymentId): PaymentResult;

    public function refund(string $paymentId, Money $amount): RefundResult;

    public function createMandate(MandateRequest $request): MandateResult;

    public function cancelMandate(string $subscriptionId): bool;

    public function pauseMandate(string $subscriptionId): bool;

    public function resumeMandate(string $subscriptionId): bool;

    /**
     * Current gateway-side state of a mandate — what
     * SyncSubscriptionStatus (M06) reconciles local drift against.
     */
    public function fetchMandate(string $subscriptionId): MandateResult;

    public function verifyWebhookSignature(string $body, string $signature): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): WebhookEvent;
}
