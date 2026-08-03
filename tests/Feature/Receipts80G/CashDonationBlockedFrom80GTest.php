<?php

declare(strict_types=1);

use App\Actions\Donations\CreateOfflineDonation;
use App\Actions\Receipts\Generate80GReceipt;
use App\Enums\PaymentMode;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->admin = User::factory()->create();
});

it('cannot issue an 80G receipt for a ₹2,500 cash donation, and explains why', function () {
    $donation = app(CreateOfflineDonation::class)->handle(
        donorName: 'Cash Donor',
        donorEmail: 'cashblocked@example.com',
        donorPhone: null,
        amount: Money::fromRupees(2500),
        paymentMode: PaymentMode::Cash,
        recordedByUserId: $this->admin->id,
    );

    $donation->donor->update([
        'pan' => 'ABCDE1234F',
        'address_line1' => 'Some Address',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);

    expect($donation->eligible_for_80g)->toBeFalse();

    $reason = app(Generate80GReceipt::class)->ineligibilityReason($donation);
    expect($reason)->toContain('not eligible for 80G');

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class);

it('allows an 80G receipt for a ₹2,000 cash donation (at the limit)', function () {
    $donation = app(CreateOfflineDonation::class)->handle(
        donorName: 'Small Cash Donor',
        donorEmail: 'cashallowed@example.com',
        donorPhone: null,
        amount: Money::fromRupees(2000),
        paymentMode: PaymentMode::Cash,
        recordedByUserId: $this->admin->id,
    );

    $donation->donor->update([
        'pan' => 'ABCDE1234F',
        'address_line1' => 'Some Address',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);

    $receipt = app(Generate80GReceipt::class)->handle($donation->fresh());

    expect($receipt->series->value)->toBe('80g');
});
