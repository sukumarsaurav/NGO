<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesEmailTemplate;
use App\Models\FundraiserRequest;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class FundraiserRequestApprovedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public function __construct(
        public readonly FundraiserRequest $request,
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
     * @return array{subject: string, body: string}
     */
    private function templateContent(): array
    {
        return $this->renderTemplate('fundraiser.approved', [
            'name' => $this->request->name,
            'title' => $this->request->title,
            'org_name' => app(SettingsRepository::class)->get('org.name'),
        ]);
    }
}
