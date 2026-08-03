<?php

declare(strict_types=1);

use App\Actions\Subscriptions\CreateSubscriptionMandate;
use App\Enums\MandateType;
use App\Models\Donor;
use App\Support\Money;

it('creates a donor and a subscription awaiting authentication', function () {
    $subscription = app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Monthly Donor',
        donorEmail: 'monthly@example.com',
        donorPhone: '9876500000',
        amount: Money::fromRupees(1000),
    );

    expect($subscription->status->value)->toBe('pending_authentication')
        ->and($subscription->amount)->toBe(100000)
        ->and($subscription->interval->value)->toBe('monthly')
        ->and($subscription->provider_subscription_id)->toStartWith('sub_fake_')
        ->and(Donor::query()->where('email', 'monthly@example.com')->exists())->toBeTrue();
});

it('reuses an existing donor by email instead of creating a duplicate', function () {
    $existing = Donor::factory()->create(['email' => 'repeatmonthly@example.com']);

    $subscription = app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Someone',
        donorEmail: 'REPEATMONTHLY@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );

    expect($subscription->donor_id)->toBe($existing->id)
        ->and(Donor::query()->count())->toBe(1);
});

it('rejects a UPI Autopay amount above the ₹15,000 per-transaction ceiling', function () {
    app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Big Donor',
        donorEmail: 'big@example.com',
        donorPhone: null,
        amount: Money::fromRupees(20000),
        mandateType: MandateType::UpiAutopay,
    );
})->throws(InvalidArgumentException::class);

it('allows the same large amount for e-mandate, which has no ceiling', function () {
    $subscription = app(CreateSubscriptionMandate::class)->handle(
        donorName: 'Big Donor',
        donorEmail: 'bigemandate@example.com',
        donorPhone: null,
        amount: Money::fromRupees(20000),
        mandateType: MandateType::Emandate,
    );

    expect($subscription->mandate_type->value)->toBe('emandate');
});
