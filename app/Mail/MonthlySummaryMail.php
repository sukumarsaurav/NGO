<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesEmailTemplate;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class MonthlySummaryMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    /**
     * @param  array{month: string, total: int, donor_count: int, new_recurring_donors: int, churned_subscriptions: int, top_campaign: string, attention: array<string, int>}  $summary
     */
    public function __construct(
        public readonly array $summary,
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
        $attention = $this->summary['attention'];
        $attentionTotal = array_sum($attention);

        return $this->renderTemplate('reports.monthly_summary', [
            'month' => $this->summary['month'],
            'total' => number_format($this->summary['total'] / 100, 2),
            'donor_count' => (string) $this->summary['donor_count'],
            'new_recurring_donors' => (string) $this->summary['new_recurring_donors'],
            'churned_subscriptions' => (string) $this->summary['churned_subscriptions'],
            'top_campaign' => $this->summary['top_campaign'],
            'attention_summary' => $attentionTotal > 0
                ? "{$attentionTotal} item(s) need attention: {$attention['halted_subscriptions']} halted subscription(s), {$attention['failed_jobs']} failed PDF job(s), {$attention['bounced_receipts']} bounced receipt email(s), {$attention['donors_missing_pan']} donor(s) missing PAN."
                : 'Nothing needs attention this month.',
            'org_name' => app(SettingsRepository::class)->get('org.name'),
        ]);
    }
}
