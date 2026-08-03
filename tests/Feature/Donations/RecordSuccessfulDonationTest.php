<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordFailedDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->initiation = app(InitiateDonation::class)->handle(
        donorName: 'Concurrent Donor',
        donorEmail: 'concurrent@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
});

it('is idempotent when the client callback and the webhook both call it for the same payment', function () {
    $result = new PaymentResult(
        paymentId: 'pay_race',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
        fee: Money::fromRupees(20),
        tax: Money::fromRupees(3.60),
        method: 'upi',
    );

    // Simulates "order is not guaranteed" — callback arrives first here,
    // webhook second; docs/modules/M05-donations-payments.md requires this
    // to be safe in either order.
    app(RecordSuccessfulDonation::class)->handle($this->initiation->order->orderId, $result);
    app(RecordSuccessfulDonation::class)->handle($this->initiation->order->orderId, $result);

    $donor = $this->initiation->donation->fresh()->donor->fresh();

    expect($donor->donation_count)->toBe(1)
        ->and($donor->total_donated)->toBe(100000);
});

it('never downgrades an already-succeeded donation back to failed', function () {
    $success = new PaymentResult(
        paymentId: 'pay_ok',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    );
    $failure = new PaymentResult(
        paymentId: 'pay_ok',
        orderId: $this->initiation->order->orderId,
        status: 'failed',
        amount: Money::fromRupees(1000),
        errorCode: 'LATE_DUPLICATE',
    );

    app(RecordSuccessfulDonation::class)->handle($this->initiation->order->orderId, $success);
    app(RecordFailedDonation::class)->handle($this->initiation->order->orderId, $failure);

    expect($this->initiation->donation->fresh()->status->value)->toBe('succeeded');
});

it('records a plain failure with the gateway error details', function () {
    $failure = new PaymentResult(
        paymentId: 'pay_declined',
        orderId: $this->initiation->order->orderId,
        status: 'failed',
        amount: Money::fromRupees(1000),
        errorCode: 'BAD_REQUEST_ERROR',
        errorDescription: 'Card declined by issuer',
        raw: ['gateway' => 'razorpay'],
    );

    $donation = app(RecordFailedDonation::class)->handle($this->initiation->order->orderId, $failure);
    $transaction = $donation->transactions()->latest()->first();

    expect($donation->status->value)->toBe('failed')
        ->and($transaction->error_code)->toBe('BAD_REQUEST_ERROR')
        ->and($transaction->error_description)->toBe('Card declined by issuer');
});

it('throws when no payment_transactions row exists for the order', function () {
    $failure = new PaymentResult(
        paymentId: 'pay_unknown',
        orderId: 'order_never_initiated',
        status: 'failed',
        amount: Money::fromRupees(1000),
    );

    app(RecordFailedDonation::class)->handle('order_never_initiated', $failure);
})->throws(RuntimeException::class, "No payment_transactions row for order 'order_never_initiated'.");
