<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\NoticeMail;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Services\Settings\SettingsRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Emails one chunk (~100) of a notice's recipients. Rate-limited via the
 * `notice-emails` limiter (see AppServiceProvider) rather than a hardcoded
 * sleep, so a stuck SMTP provider backs the job off instead of failing the
 * batch — see docs/modules/M09-notices-communication.md's edge cases.
 */
final class SendNoticeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<int>  $recipientIds
     */
    public function __construct(
        public readonly int $noticeId,
        public readonly array $recipientIds,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('notice-emails')];
    }

    public function handle(SettingsRepository $settings): void
    {
        $notice = Notice::query()->findOrFail($this->noticeId);

        $recipients = NoticeRecipient::query()
            ->with('user')
            ->whereIn('id', $this->recipientIds)
            ->where('email_status', 'pending')
            ->get();

        foreach ($recipients as $recipient) {
            $email = $recipient->user?->email;

            if (! $email) {
                $recipient->update(['email_status' => 'failed']);

                continue;
            }

            Mail::to($email)->send(new NoticeMail($notice));
            $recipient->update(['email_status' => 'sent', 'emailed_at' => now()]);
        }

        $this->markSentIfComplete($notice);
    }

    private function markSentIfComplete(Notice $notice): void
    {
        $stillPending = NoticeRecipient::query()
            ->where('notice_id', $notice->id)
            ->where('email_status', 'pending')
            ->exists();

        if (! $stillPending) {
            $notice->update(['status' => 'sent']);
        }
    }
}
