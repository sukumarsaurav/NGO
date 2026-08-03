<?php

declare(strict_types=1);

use App\Models\Campaign;

it('shows only active, recurring-enabled campaigns on /monthly-giving', function () {
    $recurring = Campaign::factory()->active()->create(['allows_recurring' => true, 'title' => 'Recurring Campaign']);
    Campaign::factory()->active()->create(['allows_recurring' => false, 'title' => 'One Time Only Campaign']);
    Campaign::factory()->completed()->create(['allows_recurring' => true, 'title' => 'Completed Recurring Campaign']);
    Campaign::factory()->create(['status' => 'draft', 'allows_recurring' => true, 'title' => 'Draft Recurring Campaign']);

    $response = $this->get(route('campaigns.monthly-giving'));

    $response->assertOk()
        ->assertSeeText('Recurring Campaign')
        ->assertDontSeeText('One Time Only Campaign')
        ->assertDontSeeText('Completed Recurring Campaign')
        ->assertDontSeeText('Draft Recurring Campaign');
});

it('shows an empty state when no campaigns accept monthly giving', function () {
    $this->get(route('campaigns.monthly-giving'))->assertOk()->assertSeeText('currently accepting monthly gifts');
});

it('survives a real cache round-trip on the second (cache-hit) request', function () {
    // The `array` cache store used everywhere else in the suite never
    // serializes anything — it can't catch a bug that only exists in
    // serialize()/unserialize(). This app's config('cache.serializable_classes')
    // is `false` (deliberate security hardening: unserialize() refuses to
    // reconstruct ANY object), so CampaignController must cache only plain
    // arrays, never Eloquent models/paginators — this pins that down against
    // a real serializing driver, which is what caught the original 500.
    config(['cache.default' => 'database']);

    Campaign::factory()->active()->create(['allows_recurring' => true, 'title' => 'Recurring Campaign']);

    $this->get(route('campaigns.monthly-giving'))->assertOk()->assertSeeText('Recurring Campaign');

    // Second request is a cache hit against the same key.
    $this->get(route('campaigns.monthly-giving'))->assertOk()->assertSeeText('Recurring Campaign');
});
