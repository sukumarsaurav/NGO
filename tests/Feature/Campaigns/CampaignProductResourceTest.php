<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Campaigns\Pages\EditCampaign;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('loads the campaign edit page with a product attached', function () {
    $campaign = Campaign::factory()->active()->create();
    CampaignProduct::factory()->for($campaign)->create(['name' => 'Medicine kit']);

    Livewire::actingAs($this->admin)
        ->test(EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertSuccessful();

    expect($campaign->products()->where('name', 'Medicine kit')->exists())->toBeTrue();
});
