<?php

declare(strict_types=1);

use App\Filament\Admin\Pages\OrganisationSettings;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('shows no 80G warning when no expiry date is set', function () {
    Livewire::actingAs($this->admin)
        ->test(OrganisationSettings::class)
        ->assertDontSeeText('expired')
        ->assertDontSeeText('expires soon');
});

it('shows the expiring warning within the 60-day window', function () {
    app(SettingsRepository::class)->set('org.80g_valid_to', now()->addDays(30)->toDateString());

    Livewire::actingAs($this->admin)
        ->test(OrganisationSettings::class)
        ->assertSeeText('expires soon');
});

it('shows the expired error once the date has passed', function () {
    app(SettingsRepository::class)->set('org.80g_valid_to', now()->subDay()->toDateString());

    Livewire::actingAs($this->admin)
        ->test(OrganisationSettings::class)
        ->assertSeeText('has expired');
});

it('shows no warning when the expiry is comfortably in the future', function () {
    app(SettingsRepository::class)->set('org.80g_valid_to', now()->addYear()->toDateString());

    Livewire::actingAs($this->admin)
        ->test(OrganisationSettings::class)
        ->assertDontSeeText('expired')
        ->assertDontSeeText('expires soon');
});
