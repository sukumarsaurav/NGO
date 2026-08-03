<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Redirect;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('lists active and completed campaigns on the index, excluding drafts', function () {
    Campaign::factory()->active()->create(['title' => 'Active One']);
    Campaign::factory()->completed()->create(['title' => 'Completed One']);
    Campaign::factory()->create(['status' => 'draft', 'title' => 'Draft One']);

    $response = $this->get(route('campaigns.index'));

    $response->assertOk()->assertSeeText('Active One')->assertSeeText('Completed One')->assertDontSeeText('Draft One');
});

it('survives a real cache round-trip on /campaigns and /causes/{slug}', function () {
    // Same regression as MonthlyGivingPageTest's cache round-trip test —
    // the `array` cache store used elsewhere in the suite never serializes
    // anything, so it can't catch a bug that only exists in
    // serialize()/unserialize(). This app's config('cache.serializable_classes')
    // is `false`, so caching a raw Eloquent Paginator (rather than plain
    // arrays) breaks on the second, cache-hit request. See
    // App\Http\Controllers\Public\CampaignController::paginateCached().
    config(['cache.default' => 'database']);

    $category = CampaignCategory::factory()->create(['slug' => 'animals']);
    Campaign::factory()->active()->for($category, 'category')->create(['title' => 'Save the strays']);

    $this->get(route('campaigns.index'))->assertOk()->assertSeeText('Save the strays');
    $this->get(route('campaigns.index'))->assertOk()->assertSeeText('Save the strays');

    $this->get('/causes/animals')->assertOk()->assertSeeText('Save the strays');
    $this->get('/causes/animals')->assertOk()->assertSeeText('Save the strays');
});

it('renders a category landing page at /causes/{slug} with its intro copy', function () {
    $category = CampaignCategory::factory()->create(['slug' => 'animals', 'name' => 'Animals', 'intro_body' => 'Unique cause copy here.']);
    Campaign::factory()->active()->for($category, 'category')->create(['title' => 'Save the strays']);

    $response = $this->get('/causes/animals');

    $response->assertOk()->assertSeeText('Animals')->assertSeeText('Unique cause copy here.')->assertSeeText('Save the strays');
});

it('resolves /causes/{category} and a campaign slugged with the same word without shadowing', function () {
    $category = CampaignCategory::factory()->create(['slug' => 'animals', 'name' => 'Animals']);
    Campaign::factory()->active()->for($category, 'category')->create(['slug' => 'animals-shelter-flood-relief', 'title' => 'Animals Shelter Flood Relief']);

    $categoryResponse = $this->get('/causes/animals');
    $campaignResponse = $this->get('/campaigns/animals-shelter-flood-relief');

    $categoryResponse->assertOk()->assertSeeText('Animals');
    $campaignResponse->assertOk()->assertSeeText('Animals Shelter Flood Relief');
});

it('shows a campaign detail page with progress and donor wall', function () {
    $campaign = Campaign::factory()->active()->create(['goal_amount' => 100000, 'raised_amount' => 0, 'donor_count' => 0]);

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Public Donor',
        donorEmail: 'publicdonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
        campaignId: $campaign->id,
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_public1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));

    $response = $this->get(route('campaigns.show', $campaign->slug));

    $response->assertOk()->assertSeeText($campaign->title)->assertSeeText('Public Donor');
});

it('masks an anonymous donor as "Anonymous" on the campaign page and never leaks the real name', function () {
    $campaign = Campaign::factory()->active()->create();

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Secret Donor',
        donorEmail: 'secretdonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
        campaignId: $campaign->id,
        isAnonymous: true,
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_anon1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));

    $response = $this->get(route('campaigns.show', $campaign->slug));

    $response->assertOk()->assertSeeText('Anonymous')->assertDontSeeText('Secret Donor');
});

it('stops a closed campaign from accepting new donations while keeping the page live', function () {
    $campaign = Campaign::factory()->create(['status' => 'closed']);

    $response = $this->get(route('campaigns.show', $campaign->slug));

    $response->assertOk()->assertSeeText('closed');
});

it('404s a draft campaign on the public route', function () {
    $campaign = Campaign::factory()->create(['status' => 'draft']);

    $this->get(route('campaigns.show', $campaign->slug))->assertNotFound();
});

it('creates a redirect when a published campaign\'s slug changes, and the old URL keeps working', function () {
    $campaign = Campaign::factory()->active()->create(['slug' => 'old-slug', 'title' => 'Redirect Test Campaign']);

    $campaign->update(['slug' => 'new-slug']);

    expect(Redirect::query()->where('from_path', '/campaigns/old-slug')->where('to_path', '/campaigns/new-slug')->exists())->toBeTrue();

    $response = $this->get('/campaigns/old-slug');
    $response->assertRedirect('/campaigns/new-slug');

    $this->get('/campaigns/new-slug')->assertOk()->assertSeeText('Redirect Test Campaign');
});

it('does not create a redirect when a draft campaign\'s slug changes', function () {
    $campaign = Campaign::factory()->create(['status' => 'draft', 'slug' => 'draft-old-slug']);

    $campaign->update(['slug' => 'draft-new-slug']);

    expect(Redirect::query()->where('from_path', '/campaigns/draft-old-slug')->exists())->toBeFalse();
});

it('collapses a redirect chain instead of chaining when a slug changes twice', function () {
    $campaign = Campaign::factory()->active()->create(['slug' => 'slug-v1']);
    $campaign->update(['slug' => 'slug-v2']);
    $campaign->update(['slug' => 'slug-v3']);

    expect(Redirect::query()->where('from_path', '/campaigns/slug-v1')->value('to_path'))->toBe('/campaigns/slug-v3')
        ->and(Redirect::query()->where('from_path', '/campaigns/slug-v2')->value('to_path'))->toBe('/campaigns/slug-v3');
});

it('404s a genuinely unknown path with no redirect', function () {
    $this->get('/campaigns/this-does-not-exist')->assertNotFound();
});
