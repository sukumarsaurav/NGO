<?php

declare(strict_types=1);

use App\Actions\Campaigns\CreateCampaign;
use App\Actions\Campaigns\PublishCampaign;
use App\Models\Campaign;
use App\Models\CampaignCategory;

it('creates a campaign in draft with a generated slug', function () {
    $category = CampaignCategory::factory()->create();

    $campaign = app(CreateCampaign::class)->handle([
        'title' => 'Help Rescue Street Dogs',
        'category_id' => $category->id,
        'story' => 'A long story about rescuing dogs.',
        'goal_amount' => 500000,
    ]);

    expect($campaign->status->value)->toBe('draft')
        ->and($campaign->slug)->toBe('help-rescue-street-dogs');
});

it('appends an incrementing suffix when the slug collides', function () {
    $category = CampaignCategory::factory()->create();
    Campaign::factory()->for($category, 'category')->create(['slug' => 'flood-relief']);

    $campaign = app(CreateCampaign::class)->handle([
        'title' => 'Flood Relief',
        'category_id' => $category->id,
        'story' => 'Story',
        'goal_amount' => 100000,
    ]);

    expect($campaign->slug)->toBe('flood-relief-2');
});

it('avoids colliding with a soft-deleted campaign\'s slug too', function () {
    $category = CampaignCategory::factory()->create();
    Campaign::factory()->for($category, 'category')->create(['slug' => 'winter-drive'])->delete();

    $campaign = app(CreateCampaign::class)->handle([
        'title' => 'Winter Drive',
        'category_id' => $category->id,
        'story' => 'Story',
        'goal_amount' => 100000,
    ]);

    expect($campaign->slug)->toBe('winter-drive-2');
});

it('publishes a draft campaign, setting starts_at', function () {
    $campaign = Campaign::factory()->create(['status' => 'draft', 'starts_at' => null]);

    $published = app(PublishCampaign::class)->handle($campaign);

    expect($published->status->value)->toBe('active')
        ->and($published->starts_at)->not->toBeNull();
});

it('refuses to publish an already-active campaign', function () {
    $campaign = Campaign::factory()->active()->create();

    app(PublishCampaign::class)->handle($campaign);
})->throws(InvalidArgumentException::class);
