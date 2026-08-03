<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Models\Donation;
use App\Models\WebhookEvent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->gateway = app(PaymentGateway::class);
    expect($this->gateway)->toBeInstanceOf(FakeGateway::class);

    $this->initiation = app(InitiateDonation::class)->handle(
        donorName: 'Anil Kumar',
        donorEmail: 'anil@example.com',
        donorPhone: '9876500001',
        amount: Money::fromRupees(1000),
    );
});

function postRazorpayWebhook(array $payload, string $signature = 'valid-test-signature')
{
    return test()->postJson(route('webhooks.razorpay'), $payload, ['X-Razorpay-Signature' => $signature]);
}

it('returns 400 and writes nothing when the signature is invalid', function () {
    $payload = FakeGateway::webhookPayload(
        'payment.captured',
        'pay_bad_sig',
        $this->initiation->order->orderId,
        100000,
    );

    $response = postRazorpayWebhook($payload, 'not-the-right-signature');

    $response->assertStatus(400);

    expect(WebhookEvent::query()->count())->toBe(0)
        ->and($this->initiation->donation->fresh()->status->value)->toBe('pending');
});

it('processes exactly once no matter how many times the same webhook is delivered', function () {
    $this->gateway->registerPaymentResult('pay_1', new PaymentResult(
        paymentId: 'pay_1',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
        fee: Money::fromRupees(20),
        tax: Money::fromRupees(3.60),
        method: 'upi',
    ));

    $payload = FakeGateway::webhookPayload(
        'payment.captured',
        'pay_1',
        $this->initiation->order->orderId,
        100000,
    );

    for ($i = 0; $i < 5; $i++) {
        $response = postRazorpayWebhook($payload);
        $response->assertOk();
    }

    expect(WebhookEvent::query()->count())->toBe(1)
        ->and(Donation::query()->where('status', 'succeeded')->count())->toBe(1);

    $donation = $this->initiation->donation->fresh();
    expect($donation->status->value)->toBe('succeeded');
});

it('moves the donation to succeeded and records fee, tax, and net_amount on payment.captured', function () {
    $this->gateway->registerPaymentResult('pay_2', new PaymentResult(
        paymentId: 'pay_2',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
        fee: Money::fromRupees(20),
        tax: Money::fromRupees(3.60),
        method: 'card',
    ));

    $payload = FakeGateway::webhookPayload('payment.captured', 'pay_2', $this->initiation->order->orderId, 100000);

    postRazorpayWebhook($payload)->assertOk();

    $donation = $this->initiation->donation->fresh();
    $transaction = $donation->transactions()->first();

    expect($donation->status->value)->toBe('succeeded')
        ->and($donation->donated_at)->not->toBeNull()
        ->and($donation->payment_mode->value)->toBe('card')
        ->and($transaction->fee)->toBe(2000)
        ->and($transaction->tax)->toBe(360)
        ->and($transaction->net_amount)->toBe(100000 - 2000 - 360);
});

it('records the error code and description on payment.failed', function () {
    $this->gateway->registerPaymentResult('pay_3', new PaymentResult(
        paymentId: 'pay_3',
        orderId: $this->initiation->order->orderId,
        status: 'failed',
        amount: Money::fromRupees(1000),
        errorCode: 'BAD_REQUEST_ERROR',
        errorDescription: 'Payment failed due to insufficient funds',
    ));

    $payload = FakeGateway::webhookPayload(
        'payment.failed',
        'pay_3',
        $this->initiation->order->orderId,
        100000,
        errorCode: 'BAD_REQUEST_ERROR',
        errorDescription: 'Payment failed due to insufficient funds',
    );

    postRazorpayWebhook($payload)->assertOk();

    $donation = $this->initiation->donation->fresh();
    $transaction = $donation->transactions()->first();

    expect($donation->status->value)->toBe('failed')
        ->and($transaction->error_code)->toBe('BAD_REQUEST_ERROR')
        ->and($transaction->error_description)->toBe('Payment failed due to insufficient funds');
});

it('never 500s and writes an ignored/failed webhook_events row for an unrecognised order_id', function () {
    $payload = FakeGateway::webhookPayload('payment.captured', 'pay_unknown', 'order_does_not_exist', 100000);

    $response = postRazorpayWebhook($payload);

    $response->assertOk();

    $event = WebhookEvent::query()->first();
    expect($event->status)->toBe('failed');
});
