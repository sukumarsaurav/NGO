<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Models\Donor;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('creates a donor, a pending donation, and a created payment_transaction', function () {
    $result = app(InitiateDonation::class)->handle(
        donorName: 'Priya Sharma',
        donorEmail: 'priya@example.com',
        donorPhone: '9876500000',
        amount: Money::fromRupees(1000),
    );

    expect($result->donation->status->value)->toBe('pending')
        ->and($result->donation->amount)->toBe(100000)
        ->and($result->order->orderId)->toStartWith('order_fake_')
        ->and(Donor::query()->where('email', 'priya@example.com')->exists())->toBeTrue();

    $transaction = $result->donation->transactions()->first();
    expect($transaction->status->value)->toBe('created')
        ->and($transaction->provider_order_id)->toBe($result->order->orderId);
});

it('reuses an existing donor by email instead of creating a duplicate', function () {
    $existing = Donor::factory()->create(['email' => 'repeat@example.com', 'name' => 'Old Name']);

    $result = app(InitiateDonation::class)->handle(
        donorName: 'New Name',
        donorEmail: 'REPEAT@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );

    expect(Donor::query()->count())->toBe(1)
        ->and($result->donation->donor_id)->toBe($existing->id)
        ->and($existing->fresh()->name)->toBe('New Name');
});

it('rejects an amount below the configured minimum', function () {
    app(InitiateDonation::class)->handle(
        donorName: 'Test Donor',
        donorEmail: 'small@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1),
    );
})->throws(InvalidArgumentException::class);
