<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Jobs\GenerateReceiptPdf;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Queue::fake();

    $this->initiation = app(InitiateDonation::class)->handle(
        donorName: 'Receipt Donor',
        donorEmail: 'receipt@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );

    $this->donation = app(RecordSuccessfulDonation::class)->handle($this->initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_receipt_1',
        orderId: $this->initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
});

it('creates a receipt with the correct amount in words and a frozen donor snapshot', function () {
    $receipt = app(GenerateDonationReceipt::class)->handle($this->donation);

    expect($receipt->amount)->toBe(100000)
        ->and($receipt->amount_in_words)->toBe('One Thousand Rupees Only')
        ->and($receipt->snapshot_data['donor_name'])->toBe('Receipt Donor')
        ->and($receipt->series->value)->toBe('donation');

    Queue::assertPushed(GenerateReceiptPdf::class, fn ($job) => $job->receiptId === $receipt->id);
});

it('does not rewrite the receipt snapshot when the donor later changes their name', function () {
    $receipt = app(GenerateDonationReceipt::class)->handle($this->donation);

    $this->donation->donor->update(['name' => 'Changed Name']);

    expect($receipt->fresh()->snapshot_data['donor_name'])->toBe('Receipt Donor');
});

it('is idempotent — calling it twice for the same donation returns the same receipt', function () {
    $first = app(GenerateDonationReceipt::class)->handle($this->donation);
    $second = app(GenerateDonationReceipt::class)->handle($this->donation);

    expect($second->id)->toBe($first->id);

    Queue::assertPushed(GenerateReceiptPdf::class, 1);
});

it('refuses to issue a receipt for a donation that has not succeeded', function () {
    $pendingInitiation = app(InitiateDonation::class)->handle(
        donorName: 'Pending Donor',
        donorEmail: 'pendingreceipt@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );

    app(GenerateDonationReceipt::class)->handle($pendingInitiation->donation);
})->throws(InvalidArgumentException::class);

it('shows an anonymous donor as "Anonymous donor" on the receipt snapshot', function () {
    $anonInitiation = app(InitiateDonation::class)->handle(
        donorName: 'Secret Giver',
        donorEmail: 'secret@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
        isAnonymous: true,
    );

    $donation = app(RecordSuccessfulDonation::class)->handle($anonInitiation->order->orderId, new PaymentResult(
        paymentId: 'pay_anon_1',
        orderId: $anonInitiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));

    $receipt = app(GenerateDonationReceipt::class)->handle($donation);

    expect($receipt->snapshot_data['donor_name'])->toBe('Anonymous donor');
});
