<?php

declare(strict_types=1);

use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\ImpactStat;
use App\Models\PressMention;
use App\Models\Testimonial;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('renders the homepage successfully with no content seeded', function () {
    $this->get('/')->assertOk();
});

it('renders a published banner as the hero', function () {
    Banner::factory()->create(['title' => 'Winter Relief Hero']);

    $this->get('/')->assertOk()->assertSeeText('Winter Relief Hero');
});

it('falls back to a static hero when no banners are published', function () {
    Banner::factory()->create(['title' => 'Hidden Banner', 'is_published' => false]);

    $response = $this->get('/');

    $response->assertOk()->assertDontSeeText('Hidden Banner');
});

it('does not render a banner outside its scheduled window', function () {
    Banner::factory()->create(['title' => 'Not Yet Live', 'starts_at' => now()->addWeek()]);
    Banner::factory()->create(['title' => 'Already Ended', 'ends_at' => now()->subWeek()]);

    $response = $this->get('/');

    $response->assertOk()->assertDontSeeText('Not Yet Live')->assertDontSeeText('Already Ended');
});

it('shows featured campaigns, falling back to recent active campaigns when none are featured', function () {
    $category = CampaignCategory::factory()->create();
    Campaign::factory()->active()->for($category, 'category')->create(['title' => 'Recent Not Featured']);

    $this->get('/')->assertOk()->assertSeeText('Recent Not Featured');
});

it('prioritises is_featured campaigns over recent ones in the featured section', function () {
    $category = CampaignCategory::factory()->create();
    Campaign::factory()->active()->for($category, 'category')->create(['title' => 'Featured One', 'is_featured' => true]);

    $this->get('/')->assertOk()->assertSeeText('Featured One');
});

it('shows impact stats, press mentions and testimonials', function () {
    ImpactStat::factory()->create(['label' => 'Dogs Rescued', 'value' => '5000', 'suffix' => '+']);
    PressMention::factory()->create(['outlet_name' => 'The Daily Times']);
    Testimonial::factory()->create(['name' => 'Priya Sharma', 'quote' => 'Wonderful cause.']);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSeeText('Dogs Rescued')
        ->assertSeeText('The Daily Times')
        ->assertSeeText('Priya Sharma');
});

it('shows browse-by-cause tiles for active categories', function () {
    CampaignCategory::factory()->create(['name' => 'Animals', 'is_active' => true]);

    $this->get('/')->assertOk()->assertSeeText('Animals');
});

it('shows a friendly empty state when there are no campaigns at all', function () {
    $this->get('/')->assertOk()->assertSeeText('check back soon');
});
