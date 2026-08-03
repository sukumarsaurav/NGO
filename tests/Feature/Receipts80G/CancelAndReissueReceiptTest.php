<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\CancelReceipt;
use App\Actions\Receipts\Generate80GReceipt;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Actions\Receipts\ReissueReceipt;
use App\Enums\ReceiptSeries;
use App\Models\Receipt;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Cancel Donor',
        donorEmail: 'canceldonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $this->donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_cancel_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
    $this->receipt = app(GenerateDonationReceipt::class)->handle($this->donation);
});

it('cancels a receipt and keeps its number', function () {
    $originalNumber = $this->receipt->receipt_number;

    $cancelled = app(CancelReceipt::class)->handle($this->receipt, 'Issued in error');

    expect($cancelled->is_cancelled)->toBeTrue()
        ->and($cancelled->cancelled_reason)->toBe('Issued in error')
        ->and($cancelled->receipt_number)->toBe($originalNumber);
});

it('refuses to cancel an already-cancelled receipt', function () {
    app(CancelReceipt::class)->handle($this->receipt, 'first');

    app(CancelReceipt::class)->handle($this->receipt->fresh(), 'second');
})->throws(InvalidArgumentException::class);

it('reissues a cancelled receipt at revision 2 with a brand new number', function () {
    $originalNumber = $this->receipt->receipt_number;
    $originalSequence = $this->receipt->sequence_number;

    $reissued = app(ReissueReceipt::class)->handle($this->receipt, 'Donor name was misspelled');

    expect($reissued->revision)->toBe(2)
        ->and($reissued->receipt_number)->not->toBe($originalNumber)
        ->and($reissued->sequence_number)->not->toBe($originalSequence)
        ->and($reissued->is_cancelled)->toBeFalse();

    $original = $this->receipt->fresh();
    expect($original->is_cancelled)->toBeTrue()
        ->and($original->receipt_number)->toBe($originalNumber);
});

it('never allows two live (non-cancelled) receipts for the same donation and series', function () {
    $again = app(GenerateDonationReceipt::class)->handle($this->donation->fresh());

    // Idempotent — returns the same live receipt, does not create a second one.
    expect($again->id)->toBe($this->receipt->id);

    app(ReissueReceipt::class)->handle($this->receipt->fresh(), 'correction');

    $liveCount = Receipt::query()
        ->where('donation_id', $this->donation->id)
        ->where('series', 'donation')
        ->where('is_cancelled', false)
        ->count();

    expect($liveCount)->toBe(1);
});

it('reissues an 80G receipt on the 80G series, not the plain donation series', function () {
    $this->donation->donor->update([
        'pan' => 'ABCDE1234F',
        'address_line1' => '221B Baker Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);
    $eightyGReceipt = app(Generate80GReceipt::class)->handle($this->donation->fresh());

    $reissued = app(ReissueReceipt::class)->handle($eightyGReceipt, 'PAN was corrected');

    expect($reissued->series)->toBe(ReceiptSeries::EightyG)
        ->and($reissued->revision)->toBe(2)
        ->and($reissued->receipt_number)->not->toBe($eightyGReceipt->receipt_number);
});
