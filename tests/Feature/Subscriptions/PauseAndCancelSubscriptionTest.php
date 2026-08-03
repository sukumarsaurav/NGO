<?php

declare(strict_types=1);

use App\Actions\Subscriptions\CancelSubscription;
use App\Actions\Subscriptions\PauseSubscription;
use App\Enums\CancelledBy;
use App\Mail\SubscriptionCancelledMail;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->subscription = Subscription::factory()->active()->create();
});

it('pauses an active subscription and can resume it', function () {
    $paused = app(PauseSubscription::class)->handle($this->subscription);
    expect($paused->status->value)->toBe('paused');

    $resumed = app(PauseSubscription::class)->resume($paused);
    expect($resumed->status->value)->toBe('active');
});

it('refuses to pause a subscription that is not active', function () {
    $this->subscription->update(['status' => 'cancelled']);

    app(PauseSubscription::class)->handle($this->subscription);
})->throws(InvalidArgumentException::class);

it('cancels a subscription and emails the donor', function () {
    $cancelled = app(CancelSubscription::class)->handle($this->subscription, CancelledBy::Donor, 'No longer able to give');

    expect($cancelled->status->value)->toBe('cancelled')
        ->and($cancelled->cancelled_by->value)->toBe('donor')
        ->and($cancelled->cancellation_reason)->toBe('No longer able to give')
        ->and($cancelled->ended_at)->not->toBeNull();

    Mail::assertQueued(SubscriptionCancelledMail::class);
});

it('refuses to cancel a subscription that is already terminal', function () {
    $this->subscription->update(['status' => 'completed']);

    app(CancelSubscription::class)->handle($this->subscription, CancelledBy::Admin);
})->throws(InvalidArgumentException::class);
