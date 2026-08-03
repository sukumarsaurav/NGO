<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

/**
 * Sprint 15 acceptance criterion — docs/03-ROADMAP.md: "Rich text from the
 * CMS cannot execute injected script." Filament's own RichEditor docs warn
 * that its state can be tampered with via a direct request to the Livewire
 * update endpoint, bypassing the client-side editor's own restrictions —
 * "trusted admin content" isn't a sufficient reason to skip sanitizing on
 * render. These tests write a `<script>` payload straight to the DB
 * (simulating exactly that bypass) and assert it never reaches the
 * response unescaped.
 */
beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('strips a script tag from a campaign story on render', function () {
    $category = CampaignCategory::factory()->create();
    $campaign = Campaign::factory()->active()->for($category, 'category')->create([
        'story' => '<p>Safe copy</p><script>alert(document.cookie)</script>',
    ]);

    $response = $this->get(route('campaigns.show', $campaign->slug));

    $response->assertOk()
        ->assertSeeText('Safe copy')
        ->assertDontSee('<script>alert(document.cookie)</script>', false);
});

it('strips a script tag from a CMS page body on render', function () {
    Page::factory()->create(['slug' => 'test-page', 'body' => '<p>Safe copy</p><script>alert(1)</script>']);

    $response = $this->get('/test-page');

    $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

it('strips a script tag from a blog post body on render', function () {
    $post = Post::factory()->published()->create(['body' => '<p>Safe copy</p><script>alert(1)</script>']);

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

it('strips a script tag from a portal notice body on render', function () {
    $user = User::factory()->create();
    $notice = Notice::factory()->published()->create(['body' => '<p>Safe copy</p><script>alert(1)</script>']);
    NoticeRecipient::factory()->create(['notice_id' => $notice->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('portal.notices.show', $notice));

    $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});
