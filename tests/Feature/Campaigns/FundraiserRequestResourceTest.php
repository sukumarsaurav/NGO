<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\FundraiserRequests\Pages\ListFundraiserRequests;
use App\Models\CampaignCategory;
use App\Models\FundraiserRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Mail::fake();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists fundraiser requests in the admin', function () {
    FundraiserRequest::factory()->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(ListFundraiserRequests::class)
        ->assertSuccessful();
});

it('approves a fundraiser request from the admin table action', function () {
    $category = CampaignCategory::factory()->create();
    $request = FundraiserRequest::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListFundraiserRequests::class)
        ->callTableAction('approve', $request, data: ['cause_category_id' => $category->id])
        ->assertHasNoTableActionErrors();

    expect($request->fresh()->status->value)->toBe('approved')
        ->and($request->fresh()->campaign_id)->not->toBeNull();
});

it('rejects a fundraiser request from the admin table action', function () {
    $request = FundraiserRequest::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListFundraiserRequests::class)
        ->callTableAction('reject', $request, data: ['reason' => 'Not enough detail provided.'])
        ->assertHasNoTableActionErrors();

    expect($request->fresh()->status->value)->toBe('rejected');
});
