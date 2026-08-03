<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;

it('allows the documented transitions', function (SubscriptionStatus $from, SubscriptionStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    [SubscriptionStatus::Created, SubscriptionStatus::Active],
    [SubscriptionStatus::PendingAuthentication, SubscriptionStatus::Active],
    [SubscriptionStatus::Active, SubscriptionStatus::Paused],
    [SubscriptionStatus::Active, SubscriptionStatus::Halted],
    [SubscriptionStatus::Active, SubscriptionStatus::Completed],
    [SubscriptionStatus::Active, SubscriptionStatus::Cancelled],
    [SubscriptionStatus::Paused, SubscriptionStatus::Active],
    [SubscriptionStatus::Halted, SubscriptionStatus::Active],
    [SubscriptionStatus::Created, SubscriptionStatus::Expired],
]);

it('rejects transitions Razorpay would never produce', function (SubscriptionStatus $from, SubscriptionStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [SubscriptionStatus::Completed, SubscriptionStatus::Active],
    [SubscriptionStatus::Cancelled, SubscriptionStatus::Active],
    [SubscriptionStatus::Expired, SubscriptionStatus::Active],
    [SubscriptionStatus::Paused, SubscriptionStatus::Halted],
]);

it('treats completed, cancelled, and expired as terminal', function () {
    expect(SubscriptionStatus::Completed->isTerminal())->toBeTrue()
        ->and(SubscriptionStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(SubscriptionStatus::Expired->isTerminal())->toBeTrue()
        ->and(SubscriptionStatus::Active->isTerminal())->toBeFalse();
});
