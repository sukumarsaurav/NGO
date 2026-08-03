<?php

declare(strict_types=1);

namespace App\Actions\Notices;

use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use Illuminate\Database\ConnectionInterface;

/**
 * Materialises the audience into `notice_recipients` rows and hands off to
 * `DispatchNoticeToRecipients` for the actual (chunked, queued) sending. See
 * docs/modules/M09-notices-communication.md's delivery pipeline.
 */
final class PublishNotice
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ResolveNoticeAudience $resolveAudience,
        private readonly DispatchNoticeToRecipients $dispatchToRecipients,
    ) {}

    public function handle(Notice $notice): Notice
    {
        $userIds = $this->resolveAudience->handle($notice->audience, $notice->audience_filter ?? []);

        $this->db->transaction(function () use ($notice, $userIds) {
            $now = now();

            foreach (array_chunk($userIds, 500) as $chunk) {
                NoticeRecipient::query()->insertOrIgnore(array_map(fn (int $userId) => [
                    'notice_id' => $notice->id,
                    'user_id' => $userId,
                    'email_status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            $notice->update([
                'status' => NoticeStatus::Sending->value,
                'published_at' => $now,
                'recipient_count' => count($userIds),
            ]);
        });

        $this->dispatchToRecipients->handle($notice->fresh());

        return $notice->fresh();
    }
}
