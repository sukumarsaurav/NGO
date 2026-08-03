<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Donations\RefundDonation;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->initiation = app(InitiateDonation::class)->handle(
        donorName: 'Refund Donor',
        donorEmail: 'refund@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );

    app(RecordSuccessfulDonation::class)->handle($this->initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_refundme',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
});

it('fully refunds a succeeded donation and flips its status to refunded', function () {
    $donation = app(RefundDonation::class)->handle($this->initiation->donation->fresh(), Money::fromRupees(1000));

    expect($donation->status->value)->toBe('refunded');

    $transaction = $donation->transactions()->first();
    expect($transaction->refund_amount)->toBe(100000)
        ->and($transaction->status->value)->toBe('refunded');
});

it('partially refunds without changing the donation status away from succeeded', function () {
    $donation = app(RefundDonation::class)->handle($this->initiation->donation->fresh(), Money::fromRupees(400));

    expect($donation->status->value)->toBe('succeeded');

    $transaction = $donation->transactions()->first();
    expect($transaction->refund_amount)->toBe(40000);
});

it('refuses to refund a donation that has not succeeded', function () {
    $pendingInitiation = app(InitiateDonation::class)->handle(
        donorName: 'Pending Donor',
        donorEmail: 'pending@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );

    app(RefundDonation::class)->handle($pendingInitiation->donation, Money::fromRupees(1000));
})->throws(InvalidArgumentException::class);

it('refuses to refund more than the remaining refundable amount', function () {
    app(RefundDonation::class)->handle($this->initiation->donation->fresh(), Money::fromRupees(1500));
})->throws(InvalidArgumentException::class);
