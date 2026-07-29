<?php

declare(strict_types=1);

use App\Facades\Settings;
use App\Filament\Admin\Pages\OrganisationSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

it('lets a super-admin load the settings page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get('/admin/organisation-settings')
        ->assertOk();
});

it('rejects a manager from the settings page with 403', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get('/admin/organisation-settings')
        ->assertForbidden();
});

it('saves a changed org name and it is readable immediately', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    // Field names on the Livewire component are underscored (org_name), not
    // the dotted setting keys (org.name) — see OrganisationSettings::fname().
    Livewire::actingAs($admin)
        ->test(OrganisationSettings::class)
        ->fillForm(['org_name' => 'New Org Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::get('org.name'))->toBe('New Org Name');
});

it('converts a rupee amount on the form into paise in storage', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(OrganisationSettings::class)
        ->fillForm(['donation_min_amount' => 75])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::get('donation.min_amount'))->toBe(7500);
});

it('never hydrates the real PAN into the form, and leaving it blank does not erase it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    app(SettingsRepository::class)->set('org.pan', 'ABCDE1234F');

    $component = Livewire::actingAs($admin)->test(OrganisationSettings::class);

    expect($component->get('data.org_pan'))->toBeNull();

    // Submitting without touching the PAN field must not wipe the real value.
    $component->fillForm(['org_name' => 'Org Name Only'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::get('org.pan'))->toBe('ABCDE1234F');
});

it('lets an admin actually set a new PAN and stores it encrypted', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(OrganisationSettings::class)
        ->fillForm(['org_pan' => 'ZYXWV9876G'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::get('org.pan'))->toBe('ZYXWV9876G');

    $raw = Setting::query()->where('key', 'org.pan')->value('value');
    expect($raw)->not->toBe('ZYXWV9876G');
});

it('converts rupee preset amounts into paise on save', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(OrganisationSettings::class)
        ->fillForm(['donation_preset_amounts' => ['500', '1000', '2500']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Settings::get('donation.preset_amounts'))->toBe([50000, 100000, 250000]);
});
