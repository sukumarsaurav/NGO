<?php

declare(strict_types=1);

use App\Actions\Subscriptions\CreateSubscriptionMandate;
use App\Models\Donation;
use App\Models\SubscriptionCharge;
use App\Models\WebhookEvent;
use App\Services\Payment\FakeGateway;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;

beforeEach(function () {
    $this->seed(EmailTemplateSeeder::class);

    $this->subscription = app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Webhook Donor',
        donorEmail: 'subwebhook@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
});

function postSubscriptionWebhook(array $payload, string $signature = 'valid-test-signature')
{
    return test()->postJson(route('webhooks.razorpay'), $payload, ['X-Razorpay-Signature' => $signature]);
}

it('activates the subscription on subscription.activated', function () {
    $payload = FakeGateway::subscriptionWebhookPayload(
        'subscription.activated',
        (string) $this->subscription->provider_subscription_id,
        'active',
    );

    postSubscriptionWebhook($payload)->assertOk();

    expect($this->subscription->fresh()->status->value)->toBe('active');
});

it('records a charge and a linked donation on subscription.charged', function () {
    postSubscriptionWebhook(FakeGateway::subscriptionWebhookPayload(
        'subscription.activated',
        (string) $this->subscription->provider_subscription_id,
        'active',
    ))->assertOk();

    postSubscriptionWebhook(FakeGateway::subscriptionWebhookPayload(
        'subscription.charged',
        (string) $this->subscription->provider_subscription_id,
        'active',
        paymentId: 'pay_webhook_charge_1',
        amountPaise: 100000,
    ))->assertOk();

    expect(SubscriptionCharge::query()->count())->toBe(1)
        ->and(Donation::query()->where('type', 'recurring')->count())->toBe(1);
});

it('processes a subscription.charged webhook exactly once no matter how many times delivered', function () {
    $payload = FakeGateway::subscriptionWebhookPayload(
        'subscription.charged',
        (string) $this->subscription->provider_subscription_id,
        'active',
        paymentId: 'pay_webhook_dup',
        amountPaise: 100000,
    );

    for ($i = 0; $i < 5; $i++) {
        postSubscriptionWebhook($payload)->assertOk();
    }

    expect(WebhookEvent::query()->count())->toBe(1)
        ->and(SubscriptionCharge::query()->count())->toBe(1);
});

it('records a failure on subscription.pending', function () {
    $payload = FakeGateway::subscriptionWebhookPayload(
        'subscription.pending',
        (string) $this->subscription->provider_subscription_id,
        'active',
        paymentId: 'pay_webhook_fail',
        errorDescription: 'Card declined',
    );

    postSubscriptionWebhook($payload)->assertOk();

    expect($this->subscription->fresh()->failed_charge_count)->toBe(1);
});
