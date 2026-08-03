<?php

declare(strict_types=1);

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

it('lists campaign categories in the admin', function () {
    CampaignCategory::factory()->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(ListCampaignCategories::class)
        ->assertSuccessful();
});
