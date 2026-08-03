<?php

declare(strict_types=1);

use App\Models\Donor;
use App\Models\Subscription;
use App\Models\User;

it('shows a donor their own subscriptions after linking by email', function () {
    $donor = Donor::factory()->create(['email' => 'portalsub@example.com']);
    Subscription::factory()->active()->for($donor)->create();

    $user = User::factory()->create(['email' => 'portalsub@example.com']);

    $response = $this->actingAs($user)->get(route('portal.subscriptions.index'));

    $response->assertOk()->assertSeeText('Active');
});

it('lets a donor pause their own subscription', function () {
    $donor = Donor::factory()->create(['email' => 'pauseowner@example.com', 'user_id' => null]);
    $user = User::factory()->create(['email' => 'pauseowner@example.com']);
    $donor->update(['user_id' => $user->id]);
    $subscription = Subscription::factory()->active()->for($donor)->create();

    $response = $this->actingAs($user)->post(route('portal.subscriptions.pause', $subscription));

    $response->assertRedirect();
    expect($subscription->fresh()->status->value)->toBe('paused');
});

it('forbids pausing another donor\'s subscription', function () {
    $owner = Donor::factory()->create(['email' => 'realowner@example.com']);
    $subscription = Subscription::factory()->active()->for($owner)->create();

    $intruder = User::factory()->create(['email' => 'intruder-sub@example.com']);

    $response = $this->actingAs($intruder)->post(route('portal.subscriptions.pause', $subscription));

    $response->assertForbidden();
});
