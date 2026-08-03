<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The "test send" button on EmailTemplateResource — already-rendered
 * subject/body (with sample data substituted) wrapped for delivery. Not
 * routed through EmailTemplateRenderer itself since the caller has already
 * rendered a preview; this just delivers it. See
 * docs/modules/M09-notices-communication.md: "the test send is what stops
 * badly-formatted emails reaching 500 donors."
 */
final class EmailTemplatePreviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $renderedSubject,
        public readonly string $renderedBody,
        public readonly ?string $orgName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[TEST] '.$this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.layout.wrapper',
            with: ['bodyHtml' => $this->renderedBody, 'orgName' => $this->orgName],
        );
    }
}
