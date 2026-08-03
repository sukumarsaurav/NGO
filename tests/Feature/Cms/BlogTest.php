<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('lists only published, past-dated posts on the blog index', function () {
    Post::factory()->published()->create(['title' => 'Live Post']);
    Post::factory()->create(['title' => 'Draft Post', 'is_published' => false]);
    Post::factory()->create(['title' => 'Future Post', 'is_published' => true, 'published_at' => now()->addWeek()]);

    $response = $this->get(route('blog.index'));

    $response->assertOk()->assertSeeText('Live Post')->assertDontSeeText('Draft Post')->assertDontSeeText('Future Post');
});

it('filters the blog index by category', function () {
    Post::factory()->published()->create(['title' => 'Impact Post', 'category' => 'impact_stories']);
    Post::factory()->published()->create(['title' => 'Event Post', 'category' => 'events']);

    $response = $this->get(route('blog.index', ['category' => 'events']));

    $response->assertOk()->assertSeeText('Event Post')->assertDontSeeText('Impact Post');
});

it('shows a published post and increments its view count', function () {
    $author = User::factory()->create(['name' => 'Jane Author']);
    $post = Post::factory()->published()->create(['title' => 'A Great Story', 'author_user_id' => $author->id]);

    $response = $this->get(route('blog.show', $post->slug));

    $response->assertOk()->assertSeeText('A Great Story')->assertSeeText('Jane Author');
    expect($post->fresh()->view_count)->toBe(1);
});

it('404s an unpublished post', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->get(route('blog.show', $post->slug))->assertNotFound();
});

it('404s a post scheduled in the future', function () {
    $post = Post::factory()->create(['is_published' => true, 'published_at' => now()->addWeek()]);

    $this->get(route('blog.show', $post->slug))->assertNotFound();
});
