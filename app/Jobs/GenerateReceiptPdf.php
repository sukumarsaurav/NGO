<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DonationThankYouMail;
use App\Models\Receipt;
use App\Services\Pdf\PdfRenderer;
use App\Services\Qr\QrCodeGenerator;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Renders a `receipts` row to PDF and emails it to the donor — the queued
 * half of GenerateDonationReceipt / Generate80GReceipt, same split as the
 * document engine (M04): the donor-facing request that triggers this never
 * waits on dompdf.
 *
 * See docs/03-ROADMAP.md's Sprint 6 acceptance criterion: "A guest donates
 * ₹500 and receives a receipt email within 5 minutes on shared hosting."
 */
final class GenerateReceiptPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $receiptId,
    ) {}

    public function handle(PdfRenderer $renderer, QrCodeGenerator $qrCodes, SettingsRepository $settings): void
    {
        $receipt = Receipt::query()->with(['donation', 'donor'])->findOrFail($this->receiptId);

        $qrSvg = $qrCodes->svg(URL::route('verify.receipt', ['uuid' => $receipt->uuid]));

        $html = $receipt->series->value === '80g'
            ? view('receipts.eighty-g', [
                'receipt' => $receipt,
                'qr' => $qrSvg,
                'signatureImage' => $this->dataUri($settings->get('org.signature_image')),
                'sealImage' => $this->dataUri($settings->get('org.seal_image')),
            ])->render()
            : view('receipts.donation', [
                'receipt' => $receipt,
                'org' => [
                    'name' => $settings->get('org.name'),
                    'address_line1' => $settings->get('org.address_line1'),
                    'address_line2' => $settings->get('org.address_line2'),
                    'city' => $settings->get('org.city'),
                    'state' => $settings->get('org.state'),
                    'pincode' => $settings->get('org.pincode'),
                    'pan' => $settings->get('org.pan'),
                ],
                'qr' => $qrSvg,
            ])->render();

        $pdfBytes = $renderer->renderHtml($html, '', 'A4', 'portrait');

        $path = "receipts/{$receipt->uuid}.pdf";
        Storage::disk('local')->put($path, $pdfBytes);

        $receipt->update(['file_path' => $path]);

        if ((bool) $settings->get('receipt.auto_email', true)) {
            $this->sendMail($receipt->fresh(['donor']));
        }
    }

    /**
     * dompdf can't reliably fetch local storage paths as <img src="...">;
     * inlining as a base64 data URI sidesteps that entirely.
     */
    private function dataUri(mixed $path): ?string
    {
        if (! $path || ! is_string($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        $contents = Storage::disk('public')->get($path);

        return 'data:'.$mime.';base64,'.base64_encode((string) $contents);
    }

    private function sendMail(Receipt $receipt): void
    {
        $email = $receipt->donor->email;

        if (! $email) {
            $receipt->update(['email_status' => 'failed']);

            return;
        }

        Mail::to($email)->queue(new DonationThankYouMail($receipt));
        $receipt->update(['emailed_at' => now(), 'email_status' => 'sent']);
    }
}
