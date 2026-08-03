<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\ExportForm10BD;
use App\Actions\Receipts\Generate80GReceipt;
use App\Services\Export\Form10BDExporter;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\FinancialYear;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

function eightyGEligibleDonation(string $email, int $amountRupees = 1000)
{
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Form10BD Donor',
        donorEmail: $email,
        donorPhone: null,
        amount: Money::fromRupees($amountRupees),
    );
    $initiation->donation->donor->update([
        'pan' => 'ABCDE1234F',
        'address_line1' => '221B Baker Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);

    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees($amountRupees),
    ));

    app(Generate80GReceipt::class)->handle($donation);

    return $donation;
}

it('exports a CSV with the prescribed Form 10BD headers', function () {
    eightyGEligibleDonation('f10bd1@example.com');

    $csv = app(ExportForm10BD::class)->handle(FinancialYear::current()->toString());

    expect($csv)->toContain('Sl. No.')
        ->and($csv)->toContain('Pre Acknowledgement Number')
        ->and($csv)->toContain('Section 80G');
});

it('groups multiple donations from the same donor in the same FY into one summed row', function () {
    $donationA = eightyGEligibleDonation('samedoor@example.com', 1000);
    $donorId = $donationA->donor_id;

    // Same donor, second donation.
    $initiation2 = app(InitiateDonation::class)->handle(
        donorName: 'Form10BD Donor',
        donorEmail: 'samedoor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );
    $donation2 = app(RecordSuccessfulDonation::class)->handle($initiation2->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation2->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));
    app(Generate80GReceipt::class)->handle($donation2);

    expect($donation2->donor_id)->toBe($donorId);

    $exporter = app(Form10BDExporter::class);
    $rows = $exporter->rows(FinancialYear::current()->toString());

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['Amount'])->toEqual(1500);
});

it('includes only the requested financial year\'s donations', function () {
    eightyGEligibleDonation('thisyear@example.com');

    $lastFy = FinancialYear::for(now()->subYear())->toString();
    $exporter = app(Form10BDExporter::class);

    expect($exporter->rows($lastFy))->toHaveCount(0)
        ->and($exporter->rows(FinancialYear::current()->toString()))->toHaveCount(1);
});

it('lists donors missing PAN or address for a financial year', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'No PAN Donor',
        donorEmail: 'nopan@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));

    $exporter = app(Form10BDExporter::class);
    $missing = $exporter->missingDonorDetails(FinancialYear::current()->toString());

    expect(collect($missing)->pluck('donor_email'))->toContain('nopan@example.com');
});
