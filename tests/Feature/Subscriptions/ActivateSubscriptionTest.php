<?php

declare(strict_types=1);

use App\Actions\Subscriptions\ActivateSubscription;
use App\Actions\Subscriptions\CreateSubscriptionMandate;
use App\Mail\SubscriptionActivatedMail;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->subscription = app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Activate Donor',
        donorEmail: 'activate@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
});

it('activates a subscription, sets started_at and next_charge_at, and emails the donor', function () {
    $activated = app(ActivateSubscription::class)->handle((string) $this->subscription->provider_subscription_id);

    expect($activated->status->value)->toBe('active')
        ->and($activated->started_at)->not->toBeNull()
        ->and($activated->next_charge_at)->not->toBeNull()
        ->and($activated->next_charge_at->isAfter($activated->started_at))->toBeTrue();

    Mail::assertQueued(SubscriptionActivatedMail::class);
});

it('is idempotent when the same activation webhook is delivered twice', function () {
    $first = app(ActivateSubscription::class)->handle((string) $this->subscription->provider_subscription_id);
    $startedAt = $first->started_at;

    $second = app(ActivateSubscription::class)->handle((string) $this->subscription->provider_subscription_id);

    expect($second->started_at->equalTo($startedAt))->toBeTrue();
    Mail::assertQueued(SubscriptionActivatedMail::class, 1);
});
