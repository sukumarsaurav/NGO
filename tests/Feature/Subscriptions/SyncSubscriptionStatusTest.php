<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;

it('corrects a subscription that the gateway shows as cancelled but we still show as active', function () {
    $subscription = Subscription::factory()->active()->create(['provider_subscription_id' => 'sub_drifted']);

    /** @var FakeGateway $gateway */
    $gateway = app(PaymentGateway::class);
    $gateway->forceMandateStatus('sub_drifted', 'cancelled');

    $this->artisan('subscriptions:sync-status')->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status->value)->toBe('cancelled')
        ->and($subscription->cancelled_by->value)->toBe('bank');
});

it('leaves subscriptions alone when the gateway agrees with our local status', function () {
    $subscription = Subscription::factory()->active()->create(['provider_subscription_id' => 'sub_agrees']);

    /** @var FakeGateway $gateway */
    $gateway = app(PaymentGateway::class);
    $gateway->forceMandateStatus('sub_agrees', 'active');

    $this->artisan('subscriptions:sync-status')->assertSuccessful();

    expect($subscription->fresh()->status->value)->toBe('active');
});

it('never touches terminal subscriptions', function () {
    $subscription = Subscription::factory()->create([
        'provider_subscription_id' => 'sub_done',
        'status' => 'completed',
    ]);

    /** @var FakeGateway $gateway */
    $gateway = app(PaymentGateway::class);
    $gateway->forceMandateStatus('sub_done', 'cancelled');

    $this->artisan('subscriptions:sync-status')->assertSuccessful();

    expect($subscription->fresh()->status->value)->toBe('completed');
});
