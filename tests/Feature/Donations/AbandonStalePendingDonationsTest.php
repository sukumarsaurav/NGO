<?php

declare(strict_types=1);

use App\Models\Donation;
use App\Models\Donor;

it('abandons pending donations older than 30 minutes, leaving recent ones alone', function () {
    $donor = Donor::factory()->create();

    $stale = Donation::factory()->for($donor)->create(['status' => 'pending', 'created_at' => now()->subMinutes(45)]);
    $recent = Donation::factory()->for($donor)->create(['status' => 'pending', 'created_at' => now()->subMinutes(5)]);
    $succeeded = Donation::factory()->for($donor)->create(['status' => 'succeeded', 'created_at' => now()->subMinutes(45)]);

    $this->artisan('donations:abandon-stale')->assertSuccessful();

    expect($stale->fresh()->status->value)->toBe('abandoned')
        ->and($recent->fresh()->status->value)->toBe('pending')
        ->and($succeeded->fresh()->status->value)->toBe('succeeded');
});
