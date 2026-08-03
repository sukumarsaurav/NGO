<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Jobs\GenerateReceiptPdf;
use App\Mail\DonationThankYouMail;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Pdf\PdfRenderer;
use App\Services\Qr\QrCodeGenerator;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Storage::fake('local');
    Mail::fake();
    Queue::fake([GenerateReceiptPdf::class]); // let this job actually run below, only fake the receipt-generation dispatch

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'PDF Donor',
        donorEmail: 'pdfdonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );

    $this->donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_pdf_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));
});

it('renders a real receipt PDF, stores it, and queues the thank-you email', function () {
    $receipt = app(GenerateDonationReceipt::class)->handle($this->donation);

    (new GenerateReceiptPdf($receipt->id))->handle(
        app(PdfRenderer::class),
        app(QrCodeGenerator::class),
        app(SettingsRepository::class),
    );

    $receipt->refresh();

    expect($receipt->file_path)->not->toBeNull()
        ->and($receipt->email_status)->toBe('sent')
        ->and($receipt->emailed_at)->not->toBeNull();

    Storage::disk('local')->assertExists($receipt->file_path);

    $bytes = Storage::disk('local')->get($receipt->file_path);
    expect($bytes)->toStartWith('%PDF-');

    Mail::assertQueued(DonationThankYouMail::class, fn ($mail) => $mail->receipt->id === $receipt->id);
});
