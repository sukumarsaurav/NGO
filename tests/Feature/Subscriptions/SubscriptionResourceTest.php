<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Donor;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists subscriptions with the MRR widget', function () {
    Subscription::factory()->active()->for(Donor::factory())->count(3)->create();

    Livewire::actingAs($this->admin)
        ->test(ListSubscriptions::class)
        ->assertSuccessful();
});
