<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\Generate80GReceipt;
use App\Jobs\GenerateReceiptPdf;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Pdf\PdfRenderer;
use App\Services\Qr\QrCodeGenerator;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Storage::fake('local');
    Mail::fake();

    $settings = app(SettingsRepository::class);
    $settings->set('org.address_line1', '1 Charity Lane');
    $settings->set('org.city', 'Pune');
    $settings->set('org.state', 'Maharashtra');
    $settings->set('org.pincode', '411001');
    $settings->set('org.80g_number', 'AAATV1234F80G01');
    $settings->set('org.12a_number', 'AAATV1234F12A01');
    $settings->set('org.authorised_signatory_name', 'Jane Doe');
    $settings->set('org.authorised_signatory_designation', 'Secretary');

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Content Test Donor',
        donorEmail: 'contenttest@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $initiation->donation->donor->update([
        'pan' => 'ABCDE1234F',
        'address_line1' => '221B Baker Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);
    $this->donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_content_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
        method: 'upi',
    ));
});

it('freezes every field the 80G receipt needs into snapshot_data', function () {
    $receipt = app(Generate80GReceipt::class)->handle($this->donation);

    $required = [
        'org_name', 'org_address', 'org_pan', 'org_80g_number', 'org_80g_valid_from',
        'org_80g_valid_to', 'org_12a_number', 'signatory_name', 'signatory_designation',
        'deduction_statement', 'purpose', 'donor_name', 'donor_pan', 'donor_address',
        'payment_mode', 'donated_at', 'financial_year',
    ];

    foreach ($required as $field) {
        expect($receipt->snapshot_data)->toHaveKey($field);
    }

    expect($receipt->snapshot_data['org_80g_number'])->toBe('AAATV1234F80G01')
        ->and($receipt->snapshot_data['donor_pan'])->toBe('ABCDE1234F')
        ->and($receipt->amount_in_words)->toBe('One Thousand Rupees Only');
});

it('does not rewrite the snapshot when settings change after issuance', function () {
    $receipt = app(Generate80GReceipt::class)->handle($this->donation);

    app(SettingsRepository::class)->set('org.address_line1', 'A Different Address');

    expect($receipt->fresh()->snapshot_data['org_address'])->toContain('1 Charity Lane')
        ->and($receipt->fresh()->snapshot_data['org_address'])->not->toContain('A Different Address');
});

it('renders a real 80G PDF with the QR code and signatory details', function () {
    $receipt = app(Generate80GReceipt::class)->handle($this->donation);

    (new GenerateReceiptPdf($receipt->id))->handle(
        app(PdfRenderer::class),
        app(QrCodeGenerator::class),
        app(SettingsRepository::class),
    );

    $receipt->refresh();
    expect($receipt->file_path)->not->toBeNull();
    Storage::disk('local')->assertExists($receipt->file_path);

    $bytes = Storage::disk('local')->get($receipt->file_path);
    expect($bytes)->toStartWith('%PDF-');
});
