<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\CancelReceipt;
use App\Models\Receipt;
use App\Services\Export\ReceiptReports;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\FinancialYear;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

function succeededDonation(string $email, int $amountRupees = 1000)
{
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Report Donor',
        donorEmail: $email,
        donorPhone: null,
        amount: Money::fromRupees($amountRupees),
    );

    return app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees($amountRupees),
    ));
}

it('reports no gaps for a clean, unbroken donation-series sequence', function () {
    succeededDonation('gapfree1@example.com');
    succeededDonation('gapfree2@example.com');

    $gaps = app(ReceiptReports::class)->gaps('donation', FinancialYear::current()->toString());

    expect($gaps)->toBe([]);
});

it('does not treat a cancelled receipt\'s retained number as a gap', function () {
    $donation = succeededDonation('cancelledkeepsnumber@example.com');
    $receipt = Receipt::query()->where('donation_id', $donation->id)->where('series', 'donation')->firstOrFail();

    app(CancelReceipt::class)->handle($receipt, 'test cancellation');

    $gaps = app(ReceiptReports::class)->gaps('donation', FinancialYear::current()->toString());

    expect($gaps)->toBe([]);
});

it('detects a genuinely missing sequence number as a gap', function () {
    $donation = succeededDonation('realgap@example.com');
    $receipt = Receipt::query()->where('donation_id', $donation->id)->where('series', 'donation')->firstOrFail();

    // Simulate data corruption: delete the row entirely, leaving a hole
    // in the sequence without a cancellation marker.
    $receipt->delete();

    $gaps = app(ReceiptReports::class)->gaps('donation', FinancialYear::current()->toString());

    expect($gaps)->toContain($receipt->sequence_number);
});

it('builds an FY-wise register with correct totals per series', function () {
    succeededDonation('register1@example.com', 1000);
    succeededDonation('register2@example.com', 500);

    $register = app(ReceiptReports::class)->register();

    $row = $register->firstWhere('series', 'donation');

    expect($row)->not->toBeNull()
        ->and($row['count'])->toBe(2)
        ->and($row['financial_year'])->toBe(FinancialYear::current()->toString());
});
