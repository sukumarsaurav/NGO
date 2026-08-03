<?php

declare(strict_types=1);

use App\Actions\Subscriptions\ActivateSubscription;
use App\Actions\Subscriptions\CreateSubscriptionMandate;
use App\Actions\Subscriptions\RecordSubscriptionCharge;
use App\Events\DonationSucceeded;
use App\Mail\SubscriptionChargeFailedMail;
use App\Mail\SubscriptionHaltedMail;
use App\Models\Donation;
use App\Models\Subscription;
use App\Models\SubscriptionCharge;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Mail::fake();
    Queue::fake();

    $this->subscription = app(ActivateSubscription::class)->handle(
        (string) app(CreateSubscriptionMandate::class)->handle(
            donorName: 'Charge Donor',
            donorEmail: 'charge@example.com',
            donorPhone: null,
            amount: Money::fromRupees(1000),
        )->provider_subscription_id
    );
});

it('creates a linked donation and a subscription_charges row on a successful charge', function () {
    $charge = app(RecordSubscriptionCharge::class)->recordSuccess(
        (string) $this->subscription->provider_subscription_id,
        'pay_charge_1',
        100000,
    );

    expect($charge->status->value)->toBe('succeeded')
        ->and($charge->cycle_number)->toBe(1)
        ->and($charge->donation_id)->not->toBeNull();

    $donation = Donation::query()->find($charge->donation_id);
    expect($donation->type->value)->toBe('recurring')
        ->and($donation->subscription_id)->toBe($this->subscription->id)
        ->and($donation->status->value)->toBe('succeeded')
        ->and($donation->amount)->toBe(100000);

    $this->subscription->refresh();
    expect($this->subscription->completed_cycles)->toBe(1)
        ->and($this->subscription->total_collected)->toBe(100000)
        ->and($this->subscription->next_charge_at)->not->toBeNull();
});

it('fires DonationSucceeded, which generates its own 80G-eligible receipt for the charge', function () {
    Event::fake([DonationSucceeded::class]);

    app(RecordSubscriptionCharge::class)->recordSuccess(
        (string) $this->subscription->provider_subscription_id,
        'pay_charge_2',
        100000,
    );

    Event::assertDispatched(DonationSucceeded::class);
});

it('is idempotent — the same provider_payment_id delivered twice records one charge', function () {
    app(RecordSubscriptionCharge::class)->recordSuccess((string) $this->subscription->provider_subscription_id, 'pay_dup', 100000);
    app(RecordSubscriptionCharge::class)->recordSuccess((string) $this->subscription->provider_subscription_id, 'pay_dup', 100000);

    expect(SubscriptionCharge::query()->count())->toBe(1)
        ->and(Donation::query()->count())->toBe(1);
});

it('increments failed_charge_count on a failed charge without halting before 3 strikes', function () {
    app(RecordSubscriptionCharge::class)->recordFailure((string) $this->subscription->provider_subscription_id, 'Insufficient balance');

    $this->subscription->refresh();
    expect($this->subscription->failed_charge_count)->toBe(1)
        ->and($this->subscription->status->value)->toBe('active');

    Mail::assertQueued(SubscriptionChargeFailedMail::class, 1);
    Mail::assertNotQueued(SubscriptionHaltedMail::class);
});

it('halts the subscription and emails donor and admin after 3 consecutive failures', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $recordCharge = app(RecordSubscriptionCharge::class);

    $recordCharge->recordFailure((string) $this->subscription->provider_subscription_id, 'reason 1');
    $recordCharge->recordFailure((string) $this->subscription->provider_subscription_id, 'reason 2');
    $recordCharge->recordFailure((string) $this->subscription->provider_subscription_id, 'reason 3');

    $this->subscription->refresh();
    expect($this->subscription->status->value)->toBe('halted')
        ->and($this->subscription->failed_charge_count)->toBe(3);

    Mail::assertQueued(SubscriptionChargeFailedMail::class, 3);
    Mail::assertQueued(SubscriptionHaltedMail::class, 2); // donor + the one admin
});

it('never double-records a cycle that already succeeded under a different payment id', function () {
    // Simulates two gateway webhooks racing for the same cycle with two
    // different payment ids — the second one to reach this action must be a
    // no-op, not a second donation for the same cycle.
    app(RecordSubscriptionCharge::class)->recordSuccess(
        (string) $this->subscription->provider_subscription_id,
        'pay_race_a',
        100000,
    );

    // Reset completed_cycles to simulate the race: the second webhook's
    // transaction computes the same cycle number as the first because it
    // was already in flight before the first one's update committed. A
    // query-builder update (not ->update() on the stale $this->subscription
    // instance) is required here — Eloquent skips the UPDATE entirely when
    // the new value matches the model's own stale in-memory original.
    Subscription::whereKey($this->subscription->id)->update(['completed_cycles' => 0]);

    $charge = app(RecordSubscriptionCharge::class)->recordSuccess(
        (string) $this->subscription->provider_subscription_id,
        'pay_race_b',
        100000,
    );

    expect($charge->provider_payment_id)->toBe('pay_race_a')
        ->and(SubscriptionCharge::query()->count())->toBe(1)
        ->and(Donation::query()->count())->toBe(1);
});

it('resets failed_charge_count to zero on any successful charge', function () {
    $recordCharge = app(RecordSubscriptionCharge::class);

    $recordCharge->recordFailure((string) $this->subscription->provider_subscription_id, 'reason 1');
    $recordCharge->recordFailure((string) $this->subscription->provider_subscription_id, 'reason 2');
    $this->subscription->refresh();
    expect($this->subscription->failed_charge_count)->toBe(2);

    $recordCharge->recordSuccess((string) $this->subscription->provider_subscription_id, 'pay_recover', 100000);

    $this->subscription->refresh();
    expect($this->subscription->failed_charge_count)->toBe(0);
});
