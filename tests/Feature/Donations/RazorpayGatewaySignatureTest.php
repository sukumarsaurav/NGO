<?php

declare(strict_types=1);

use App\Services\Payment\RazorpayGateway;

/**
 * Signature verification is pure HMAC computation — no network call to
 * Razorpay is involved, so this is safe to run in the normal suite even
 * without real API credentials.
 */
it('verifies a correctly-signed webhook body and rejects a tampered one', function () {
    $secret = 'whsec_test_12345';
    $gateway = new RazorpayGateway(key: 'rzp_test_x', secret: 'secret_x', webhookSecret: $secret);

    $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_1']]]]);
    $validSignature = hash_hmac('sha256', $body, $secret);

    expect($gateway->verifyWebhookSignature($body, $validSignature))->toBeTrue()
        ->and($gateway->verifyWebhookSignature($body, 'wrong-signature'))->toBeFalse()
        ->and($gateway->verifyWebhookSignature($body.'tampered', $validSignature))->toBeFalse();
});

it('parses a payment.captured webhook payload into orderId and paymentId', function () {
    $gateway = new RazorpayGateway(key: 'rzp_test_x', secret: 'secret_x', webhookSecret: 'whsec');

    $event = $gateway->parseWebhook([
        'id' => 'evt_1',
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => ['id' => 'pay_abc', 'order_id' => 'order_xyz'],
            ],
        ],
    ]);

    expect($event->eventId)->toBe('evt_1')
        ->and($event->eventType)->toBe('payment.captured')
        ->and($event->paymentId)->toBe('pay_abc')
        ->and($event->orderId)->toBe('order_xyz');
});
