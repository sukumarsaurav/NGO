<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Donations\Pages\ListDonations;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists donations', function () {
    Donation::factory()->for(Donor::factory())->count(3)->create();

    Livewire::actingAs($this->admin)
        ->test(ListDonations::class)
        ->assertSuccessful();
});

it('records an offline donation through the header action', function () {
    Livewire::actingAs($this->admin)
        ->test(ListDonations::class)
        ->callAction('recordOfflineDonation', data: [
            'donorName' => 'Cheque Donor',
            'donorEmail' => 'chequedonor@example.com',
            'donorPhone' => null,
            'amount' => '2000',
            'paymentMode' => 'cheque',
            'notes' => null,
        ]);

    expect(Donation::query()->where('is_offline', true)->count())->toBe(1);
});
