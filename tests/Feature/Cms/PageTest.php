<?php

declare(strict_types=1);

use App\Models\Page;
use Database\Seeders\HomepageContentSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('renders a published CMS page by slug', function () {
    Page::factory()->create(['slug' => 'our-mission', 'title' => 'Our Mission', 'body' => '<p>We help people.</p>']);

    $response = $this->get('/our-mission');

    $response->assertOk()->assertSeeText('Our Mission')->assertSeeText('We help people.');
});

it('404s an unpublished CMS page', function () {
    Page::factory()->create(['slug' => 'hidden-page', 'is_published' => false]);

    $this->get('/hidden-page')->assertNotFound();
});

it('404s a genuinely unknown slug', function () {
    $this->get('/this-page-does-not-exist')->assertNotFound();
});

it('serves the seeded about, privacy-policy and terms-conditions pages', function () {
    $this->seed(HomepageContentSeeder::class);

    $this->get('/about')->assertOk()->assertSeeText('About Us');
    $this->get('/privacy-policy')->assertOk()->assertSeeText('Privacy Policy');
    $this->get('/terms-conditions')->assertOk()->assertSeeText('Terms & Conditions');
});
