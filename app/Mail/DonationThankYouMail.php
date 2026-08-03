<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesEmailTemplate;
use App\Models\Receipt;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

final class DonationThankYouMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public function __construct(
        public readonly Receipt $receipt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->templateContent()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.layout.wrapper',
            with: ['bodyHtml' => $this->templateContent()['body'], 'orgName' => app(SettingsRepository::class)->get('org.name')],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if (! $this->receipt->file_path || ! Storage::disk('local')->exists($this->receipt->file_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->receipt->file_path)
                ->as("Receipt-{$this->receipt->receipt_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function templateContent(): array
    {
        return $this->renderTemplate('donation.thank_you', [
            'donor_name' => $this->receipt->snapshot_data['donor_name'] ?? 'there',
            'amount' => number_format($this->receipt->amount / 100, 2),
            'receipt_number' => $this->receipt->receipt_number,
            'org_name' => app(SettingsRepository::class)->get('org.name'),
        ]);
    }
}
