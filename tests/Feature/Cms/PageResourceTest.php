<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Pages\Pages\CreatePage as CreatePagePage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists CMS pages in the admin', function () {
    Livewire::actingAs($this->admin)->test(ListPages::class)->assertSuccessful();
});

it('creates a CMS page through the admin', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePagePage::class)
        ->fillForm([
            'title' => 'Our Impact',
            'slug' => 'our-impact',
            'body' => '<p>We have helped thousands.</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::query()->where('slug', 'our-impact')->exists())->toBeTrue();
});

it('blocks a page slug that collides with a real route', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePagePage::class)
        ->fillForm([
            'title' => 'Campaigns',
            'slug' => 'campaigns',
            'body' => '<p>Whatever.</p>',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    expect(Page::query()->where('slug', 'campaigns')->exists())->toBeFalse();
});
