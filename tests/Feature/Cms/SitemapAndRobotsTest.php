<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Page;
use App\Models\Post;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('serves a sitemap index pointing at the four child sitemaps', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    foreach (['sitemap-pages.xml', 'sitemap-causes.xml', 'sitemap-campaigns.xml', 'sitemap-blog.xml'] as $child) {
        expect($response->getContent())->toContain($child);
    }
});

it('includes active and completed campaigns in the campaigns sitemap but excludes drafts', function () {
    $category = CampaignCategory::factory()->create();
    $active = Campaign::factory()->active()->for($category, 'category')->create();
    $completed = Campaign::factory()->completed()->for($category, 'category')->create();
    $draft = Campaign::factory()->for($category, 'category')->create(['status' => 'draft']);

    $xml = $this->get('/sitemap-campaigns.xml')->getContent();

    expect($xml)->toContain($active->slug)
        ->and($xml)->toContain($completed->slug)
        ->and($xml)->not->toContain($draft->slug);
});

it('includes published posts and pages in their sitemaps', function () {
    $post = Post::factory()->published()->create();
    $page = Page::factory()->create();

    expect($this->get('/sitemap-blog.xml')->getContent())->toContain($post->slug);
    expect($this->get('/sitemap-pages.xml')->getContent())->toContain($page->slug);
});

it('includes active categories in the causes sitemap', function () {
    $category = CampaignCategory::factory()->create(['is_active' => true]);

    expect($this->get('/sitemap-causes.xml')->getContent())->toContain($category->slug);
});

it('serves robots.txt disallowing portal, admin, manager and verify, pointing at the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $content = $response->getContent();
    expect($content)->toContain('Disallow: /portal/')
        ->and($content)->toContain('Disallow: /admin/')
        ->and($content)->toContain('Disallow: /manager/')
        ->and($content)->toContain('Disallow: /verify/')
        ->and($content)->toContain('Sitemap: '.route('sitemap.index'));
});

it('does not mark the general /donate page noindex', function () {
    expect($this->get('/donate')->getContent())->not->toContain('noindex');
});
