<?php

declare(strict_types=1);

use App\Actions\Campaigns\CreateCampaign;
use App\Filament\Admin\Resources\CampaignCategories\Pages\ListCampaignCategories;
use App\Filament\Admin\Resources\Campaigns\Pages\CreateCampaign as CreateCampaignPage;
use App\Filament\Admin\Resources\Campaigns\Pages\ListCampaigns;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists campaigns in the admin', function () {
    Campaign::factory()->for(CampaignCategory::factory(), 'category')->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(ListCampaigns::class)
        ->assertSuccessful();
});

it('creates a campaign through the admin form via the CreateCampaign action', function () {
    $category = CampaignCategory::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CreateCampaignPage::class)
        ->fillForm([
            'title' => 'Admin Created Campaign',
            'category_id' => $category->id,
            'story' => 'A story with enough words to pass review.',
            'goal_amount' => '10000',
            'slug' => 'admin-created-campaign',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Campaign::query()->where('slug', 'admin-created-campaign')->exists())->toBeTrue();
});

// Regression: the slug auto-fills from the title when the title field loses focus, and used to
// store a Stringable object instead of a string. Saving then crashed in CreateCampaign with a
// TypeError, which the admin saw only as "Error while loading page". The test above supplies its
// own slug, so it never took this path — this one types a title and lets the form do the rest.
it('creates a campaign when the slug is auto-filled from the title', function () {
    $category = CampaignCategory::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CreateCampaignPage::class)
        ->set('data.title', 'Help Imran Get Life-Changing Stem Cell Therapy')
        ->fillForm([
            'category_id' => $category->id,
            'story' => 'A story with enough words to pass review.',
            'goal_amount' => '250000',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = Campaign::query()->latest('id')->first();

    expect($campaign->slug)->toBe('help-imran-get-life-changing-stem-cell-therapy')
        ->and($campaign->title)->toBe('Help Imran Get Life-Changing Stem Cell Therapy');
});

it('tells the admin when an auto-filled slug is already taken instead of failing', function () {
    $category = CampaignCategory::factory()->create();
    Campaign::factory()->for($category, 'category')->create(['slug' => 'same-title']);

    Livewire::actingAs($this->admin)
        ->test(CreateCampaignPage::class)
        ->set('data.title', 'Same Title')
        ->fillForm(['category_id' => $category->id, 'story' => 'A story with enough words.', 'goal_amount' => '1000'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('accepts a Stringable or blank slug in the CreateCampaign action', function () {
    $category = CampaignCategory::factory()->create();
    $base = ['title' => 'Same Title', 'category_id' => $category->id, 'story' => 'A story.', 'goal_amount' => 100000];

    $first = app(CreateCampaign::class)->handle($base + ['slug' => str('Same Title')->slug()]);
    $second = app(CreateCampaign::class)->handle($base + ['slug' => '']);

    expect($first->slug)->toBe('same-title')
        ->and($second->slug)->toBe('same-title-2');
});

it('lists campaign categories in the admin', function () {
    CampaignCategory::factory()->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(ListCampaignCategories::class)
        ->assertSuccessful();
});
