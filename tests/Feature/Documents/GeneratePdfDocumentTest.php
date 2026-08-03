<?php

declare(strict_types=1);

use App\Actions\Documents\IssueCertificate;
use App\Actions\Documents\IssueIdCard;
use App\Jobs\GeneratePdfDocument;
use App\Mail\IdCardIssuedMail;
use App\Models\DocumentTemplate;
use App\Models\Member;
use App\Models\User;
use App\Services\Pdf\PdfRenderer;
use App\Services\Pdf\TemplateEngine;
use App\Services\Qr\QrCodeGenerator;
use Database\Seeders\DocumentTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DocumentTemplateSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

it('renders the queued document to a PDF file and flips it to issued within the job', function () {
    $admin = User::factory()->create();
    $member = Member::factory()->create();

    $document = app(IssueIdCard::class)->handle($member, $admin->id);

    (new GeneratePdfDocument($document->id))->handle(
        app(PdfRenderer::class),
        app(TemplateEngine::class),
        app(QrCodeGenerator::class),
    );

    $document->refresh();

    expect($document->status->value)->toBe('issued')
        ->and($document->file_path)->not->toBeNull();

    Storage::disk('local')->assertExists($document->file_path);

    $bytes = Storage::disk('local')->get($document->file_path);
    expect($bytes)->toStartWith('%PDF-');
});

it('queues an email to the member once the document is issued', function () {
    $admin = User::factory()->create();
    $member = Member::factory()->create();

    $document = app(IssueIdCard::class)->handle($member, $admin->id);

    (new GeneratePdfDocument($document->id))->handle(
        app(PdfRenderer::class),
        app(TemplateEngine::class),
        app(QrCodeGenerator::class),
    );

    Mail::assertQueued(IdCardIssuedMail::class);
});

it('renders Hindi (Devanagari) text without throwing', function () {
    $admin = User::factory()->create();
    $member = Member::factory()->create();

    $template = DocumentTemplate::factory()->create([
        'type' => 'certificate',
        'body_html' => '<p>{{ $member->name }} — प्रमाण पत्र</p>',
        'is_default' => false,
    ]);

    $document = app(IssueCertificate::class)
        ->handle($member, 'हिन्दी प्रमाणपत्र', $admin->id, $template);

    (new GeneratePdfDocument($document->id))->handle(
        app(PdfRenderer::class),
        app(TemplateEngine::class),
        app(QrCodeGenerator::class),
    );

    $document->refresh();

    expect($document->status->value)->toBe('issued');
    Storage::disk('local')->assertExists($document->file_path);
});

it('sizes the ID card page at exactly 85.6 x 54 mm', function () {
    $renderer = app(PdfRenderer::class);
    $reflection = new ReflectionMethod($renderer, 'paperSize');
    $reflection->setAccessible(true);

    $template = DocumentTemplate::factory()->idCard()->make();

    [$x0, $y0, $width, $height] = $reflection->invoke($renderer, $template->page_size);

    $mmToPt = 2.83464567;

    expect($x0)->toBe(0)
        ->and($y0)->toBe(0)
        ->and($width)->toEqualWithDelta(85.6 * $mmToPt, 0.001)
        ->and($height)->toEqualWithDelta(54 * $mmToPt, 0.001);
});
