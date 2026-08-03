<?php

declare(strict_types=1);

use App\Filament\Manager\Resources\Members\MemberResource;
use App\Filament\Manager\Resources\Members\Pages\CreateMember;
use App\Filament\Manager\Resources\Members\Pages\ListMembers;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->manager = User::factory()->create();
    $this->manager->assignRole('manager');
    $this->department = Department::factory()->create(['manager_user_id' => $this->manager->id]);
    $this->otherDepartment = Department::factory()->create();

    $this->actingAs($this->manager);
});

it('scopes the member list to only the managers own department', function () {
    $inDept = Member::factory()->create(['department_id' => $this->department->id]);
    $outsideDept = Member::factory()->create(['department_id' => $this->otherDepartment->id]);

    $ids = MemberResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($inDept->id)
        ->not->toContain($outsideDept->id);
});

it('rejects creating a member in a department the manager does not manage, even by posting a raw id', function () {
    Livewire::test(CreateMember::class)
        ->fillForm([
            'user' => ['name' => 'Sneaky Member', 'email' => 'sneaky@example.com'],
            'department_id' => $this->otherDepartment->id,
            'joined_on' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['department_id']);

    expect(Member::query()->whereHas('user', fn ($q) => $q->where('email', 'sneaky@example.com'))->exists())->toBeFalse();
});

it('lists members through the actual Livewire page scoped correctly', function () {
    $inDept = Member::factory()->create(['department_id' => $this->department->id]);
    Member::factory()->create(['department_id' => $this->otherDepartment->id]);

    Livewire::test(ListMembers::class)
        ->assertCanSeeTableRecords([$inDept]);
});
