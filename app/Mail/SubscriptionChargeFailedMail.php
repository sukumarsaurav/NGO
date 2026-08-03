<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesEmailTemplate;
use App\Models\Subscription;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tone matters — a failed charge is usually a bank issue, not negligence.
 * See docs/modules/M06-recurring-autopay.md: "these are donors, not
 * delinquent customers."
 */
final class SubscriptionChargeFailedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly ?string $reason = null,
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
        return $this->renderTemplate('subscription.charge_failed', [
            'donor_name' => $this->subscription->donor->name,
            'amount' => number_format($this->subscription->amount / 100, 2),
            'reason' => $this->reason ?: 'a bank-side issue',
            'org_name' => app(SettingsRepository::class)->get('org.name'),
        ]);
    }
}
