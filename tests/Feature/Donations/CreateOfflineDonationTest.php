<?php

declare(strict_types=1);

use App\Actions\Donations\CreateOfflineDonation;
use App\Enums\PaymentMode;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
});

it('records a cheque donation as succeeded with no payment_transactions row', function () {
    $donation = app(CreateOfflineDonation::class)->handle(
        donorName: 'Offline Donor',
        donorEmail: 'offline@example.com',
        donorPhone: null,
        amount: Money::fromRupees(5000),
        paymentMode: PaymentMode::Cheque,
        recordedByUserId: $this->admin->id,
    );

    expect($donation->status->value)->toBe('succeeded')
        ->and($donation->is_offline)->toBeTrue()
        ->and($donation->eligible_for_80g)->toBeTrue()
        ->and($donation->transactions()->count())->toBe(0)
        ->and($donation->recorded_by_user_id)->toBe($this->admin->id);
});

it('marks cash above the 80G limit as ineligible automatically', function () {
    $donation = app(CreateOfflineDonation::class)->handle(
        donorName: 'Cash Donor',
        donorEmail: 'cash@example.com',
        donorPhone: null,
        amount: Money::fromRupees(3000), // above the seeded ₹2,000 cash_80g_limit
        paymentMode: PaymentMode::Cash,
        recordedByUserId: $this->admin->id,
    );

    expect($donation->eligible_for_80g)->toBeFalse();
});

it('keeps cash at or below the 80G limit eligible', function () {
    $donation = app(CreateOfflineDonation::class)->handle(
        donorName: 'Small Cash Donor',
        donorEmail: 'smallcash@example.com',
        donorPhone: null,
        amount: Money::fromRupees(2000),
        paymentMode: PaymentMode::Cash,
        recordedByUserId: $this->admin->id,
    );

    expect($donation->eligible_for_80g)->toBeTrue();
});
