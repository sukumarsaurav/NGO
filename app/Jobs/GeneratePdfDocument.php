<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Mail\AppointmentLetterMail;
use App\Mail\CertificateIssuedMail;
use App\Mail\IdCardIssuedMail;
use App\Models\IssuedDocument;
use App\Services\Pdf\PdfRenderer;
use App\Services\Pdf\TemplateEngine;
use App\Services\Qr\QrCodeGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renders an `issued_documents` row (status `queued`) to a PDF file and
 * flips it to `issued`. See docs/03-ROADMAP.md's Sprint 4 acceptance
 * criteria: "Issuing an ID card queues a job; the PDF exists within 30
 * seconds."
 */
final class GeneratePdfDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $issuedDocumentId,
    ) {}

    public function handle(
        PdfRenderer $renderer,
        TemplateEngine $templateEngine,
        QrCodeGenerator $qrCodes,
    ): void {
        $document = IssuedDocument::query()->with(['template', 'member'])->findOrFail($this->issuedDocumentId);
        $template = $document->template;

        if (! $template) {
            throw new RuntimeException("IssuedDocument #{$document->id} has no template.");
        }

        $qrSvg = $template->qr_enabled ? $qrCodes->svg($document->qr_payload) : null;

        $body = $templateEngine->render($template->body_html, [
            'member' => (object) $document->snapshot_data,
            'document' => $document,
            'qr' => $qrSvg,
        ]);

        if ($qrSvg) {
            $body .= $this->qrOverlayHtml($qrSvg, $template->qr_position);
        }

        $pdfBytes = $renderer->render($template, $body);

        $path = "documents/{$document->uuid}.pdf";
        Storage::disk('local')->put($path, $pdfBytes);

        $document->update(['file_path' => $path, 'status' => DocumentStatus::Issued->value]);

        $this->sendMail($document->fresh(['member.user']));
    }

    /**
     * @param  array<string, mixed>|null  $position
     */
    private function qrOverlayHtml(string $qrSvg, ?array $position): string
    {
        $x = $position['x'] ?? 5;
        $y = $position['y'] ?? 5;
        $size = $position['size'] ?? 20;

        return sprintf(
            '<div style="position:absolute;left:%smm;top:%smm;width:%smm;height:%smm;">%s</div>',
            $x, $y, $size, $size, $qrSvg
        );
    }

    private function sendMail(IssuedDocument $document): void
    {
        $email = $document->member->user->email ?? null;

        if (! $email) {
            return;
        }

        $mailable = match ($document->type) {
            DocumentType::IdCard => new IdCardIssuedMail($document),
            DocumentType::AppointmentLetter => new AppointmentLetterMail($document),
            DocumentType::Certificate => new CertificateIssuedMail($document),
        };

        Mail::to($email)->queue($mailable);
    }
}
