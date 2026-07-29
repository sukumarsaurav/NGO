<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Designations\Pages\CreateDesignation;
use App\Models\Designation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('creates a designation with an auto-generated slug', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateDesignation::class)
        ->fillForm(['title' => 'Regional Coordinator', 'rank' => 10])
        ->call('create')
        ->assertHasNoFormErrors();

    $designation = Designation::where('title', 'Regional Coordinator')->first();

    expect($designation->slug)->toBe('regional-coordinator')
        ->and($designation->rank)->toBe(10);
});
