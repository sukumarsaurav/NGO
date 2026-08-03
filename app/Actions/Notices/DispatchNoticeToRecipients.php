<?php

declare(strict_types=1);

namespace App\Actions\Notices;

use App\Enums\NoticeStatus;
use App\Jobs\SendNoticeEmail;
use App\Models\Notice;

/**
 * Chunks `notice_recipients` and queues one `SendNoticeEmail` job per chunk
 * of 100 — see docs/modules/M09-notices-communication.md: sending to 500
 * members in a single request trips shared-hosting SMTP rate limits.
 *
 * A notice with `send_email = false` (portal-only) or zero recipients has
 * nothing to queue, so it's marked `sent` immediately rather than waiting
 * on a job that will never run.
 */
final class DispatchNoticeToRecipients
{
    private const CHUNK_SIZE = 100;

    public function handle(Notice $notice): void
    {
        if (! $notice->send_email || $notice->recipient_count === 0) {
            $notice->update(['status' => NoticeStatus::Sent->value]);

            return;
        }

        $recipientIds = $notice->recipients()->pluck('id')->all();

        foreach (array_chunk($recipientIds, self::CHUNK_SIZE) as $chunk) {
            SendNoticeEmail::dispatch($notice->id, $chunk);
        }
    }
}
