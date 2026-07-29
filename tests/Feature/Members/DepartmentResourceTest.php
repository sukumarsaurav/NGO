<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Admin\Resources\Departments\Pages\EditDepartment;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('auto-generates a slug from the name', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateDepartment::class)
        ->fillForm(['name' => 'North Zone Operations'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::where('name', 'North Zone Operations')->first()->slug)
        ->toBe('north-zone-operations');
});

it('excludes itself and its descendants from its own parent options — no cycles', function () {
    $grandparent = Department::factory()->create();
    $parent = Department::factory()->create(['parent_id' => $grandparent->id]);
    $child = Department::factory()->create(['parent_id' => $parent->id]);

    // Editing $parent, the options for parent_id must exclude $parent itself
    // and $child (its descendant) — either would create a cycle.
    $component = Livewire::actingAs($this->admin)
        ->test(EditDepartment::class, ['record' => $parent->getRouteKey()]);

    $options = $component->instance()->form->getComponent('parent_id')->getOptions();

    expect($options)->not->toHaveKey($parent->id)
        ->not->toHaveKey($child->id)
        ->toHaveKey($grandparent->id);
});

it('assigns a manager to a department', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    Livewire::actingAs($this->admin)
        ->test(CreateDepartment::class)
        ->fillForm(['name' => 'Fundraising', 'manager_user_id' => $manager->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::where('name', 'Fundraising')->first()->manager_user_id)->toBe($manager->id);
});
