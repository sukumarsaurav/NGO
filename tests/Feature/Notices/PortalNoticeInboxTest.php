<?php

declare(strict_types=1);

use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;

beforeEach(function () {
    $this->seed(EmailTemplateSeeder::class);
    $this->user = User::factory()->create();
});

it('lists only published notices addressed to the current user', function () {
    $notice = Notice::factory()->published()->create();
    NoticeRecipient::factory()->create(['notice_id' => $notice->id, 'user_id' => $this->user->id]);

    $draft = Notice::factory()->create();
    NoticeRecipient::factory()->create(['notice_id' => $draft->id, 'user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->get(route('portal.notices.index'));

    $response->assertOk()->assertSeeText($notice->title)->assertDontSeeText($draft->title);
});

it('marks a notice read on first open and increments the read count exactly once', function () {
    $notice = Notice::factory()->published()->create(['read_count' => 0]);
    $recipient = NoticeRecipient::factory()->create(['notice_id' => $notice->id, 'user_id' => $this->user->id]);

    $this->actingAs($this->user)->get(route('portal.notices.show', $notice))->assertOk();
    $this->actingAs($this->user)->get(route('portal.notices.show', $notice))->assertOk();

    expect($recipient->fresh()->read_at)->not->toBeNull()
        ->and($notice->fresh()->read_count)->toBe(1);
});

it('404s a notice the user was not sent', function () {
    $notice = Notice::factory()->published()->create();

    $this->actingAs($this->user)->get(route('portal.notices.show', $notice))->assertNotFound();
});
