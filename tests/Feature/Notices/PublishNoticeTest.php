<?php

declare(strict_types=1);

use App\Actions\Notices\PublishNotice;
use App\Enums\NoticeAudience;
use App\Enums\NoticeStatus;
use App\Jobs\SendNoticeEmail;
use App\Mail\NoticeMail;
use App\Models\Member;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(EmailTemplateSeeder::class);
});

it('materialises the audience into notice_recipients and queues chunked send jobs', function () {
    Queue::fake();

    $members = Member::factory()->active()->count(3)->create();

    $notice = Notice::factory()->create(['audience' => NoticeAudience::AllMembers->value]);

    app(PublishNotice::class)->handle($notice);

    $notice->refresh();

    expect($notice->status)->toBe(NoticeStatus::Sending)
        ->and($notice->recipient_count)->toBe(3)
        ->and(NoticeRecipient::query()->where('notice_id', $notice->id)->count())->toBe(3);

    foreach ($members as $member) {
        expect(NoticeRecipient::query()->where('notice_id', $notice->id)->where('user_id', $member->user_id)->exists())->toBeTrue();
    }

    Queue::assertPushed(SendNoticeEmail::class);
});

it('marks a notice sent immediately when send_email is false', function () {
    Member::factory()->active()->create();

    $notice = Notice::factory()->create(['audience' => NoticeAudience::AllMembers->value, 'send_email' => false]);

    app(PublishNotice::class)->handle($notice);

    expect($notice->fresh()->status)->toBe(NoticeStatus::Sent);
});

it('marks a notice sent immediately when there are zero recipients', function () {
    $notice = Notice::factory()->create(['audience' => NoticeAudience::AllMembers->value]);

    app(PublishNotice::class)->handle($notice);

    expect($notice->fresh()->status)->toBe(NoticeStatus::Sent)
        ->and($notice->fresh()->recipient_count)->toBe(0);
});

it('actually delivers the email and updates recipient email_status when the queue runs sync', function () {
    Mail::fake();

    $member = Member::factory()->active()->create();
    $notice = Notice::factory()->create(['audience' => NoticeAudience::AllMembers->value]);

    app(PublishNotice::class)->handle($notice);

    $recipient = NoticeRecipient::query()->where('notice_id', $notice->id)->first();

    expect($recipient->email_status)->toBe('sent')
        ->and($recipient->emailed_at)->not->toBeNull()
        ->and($notice->fresh()->status)->toBe(NoticeStatus::Sent);

    Mail::assertSent(NoticeMail::class);
});
