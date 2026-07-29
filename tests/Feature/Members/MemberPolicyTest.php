<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets super-admin view any member via the Gate::before bypass', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $member = Member::factory()->for(User::factory())->create();

    expect($admin->can('view', $member))->toBeTrue();
});

it('lets a member view and update their own record', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->for($user)->create();

    expect($user->can('view', $member))->toBeTrue()
        ->and($user->can('update', $member))->toBeTrue();
});

it('never lets a member view another members record', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $otherMember = Member::factory()->for(User::factory())->create();

    expect($user->can('view', $otherMember))->toBeFalse()
        ->and($user->can('update', $otherMember))->toBeFalse();
});

it('never lets a donor view a member record at all', function () {
    $donor = User::factory()->create();
    $donor->assignRole('donor');

    $member = Member::factory()->for(User::factory())->create();

    expect($donor->can('view', $member))->toBeFalse()
        ->and($donor->can('viewAny', Member::class))->toBeFalse();
});

it('lets a manager view a member in a department they manage', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $department = Department::factory()->create(['manager_user_id' => $manager->id]);
    $member = Member::factory()->for(User::factory())->create(['department_id' => $department->id]);

    expect($manager->can('view', $member))->toBeTrue()
        ->and($manager->can('update', $member))->toBeTrue();
});

it('rejects a manager viewing a member outside their department — the IDOR case', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    Department::factory()->create(['manager_user_id' => $manager->id]);

    $otherDepartment = Department::factory()->create();
    $member = Member::factory()->for(User::factory())->create(['department_id' => $otherDepartment->id]);

    expect($manager->can('view', $member))->toBeFalse()
        ->and($manager->can('update', $member))->toBeFalse();
});

it('lets a manager see a member in a nested sub-department they manage', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $parent = Department::factory()->create(['manager_user_id' => $manager->id]);
    $child = Department::factory()->create(['parent_id' => $parent->id]);
    $member = Member::factory()->for(User::factory())->create(['department_id' => $child->id]);

    expect($manager->can('view', $member))->toBeTrue();
});

it('never lets anyone but the Gate::before bypass delete a member', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $department = Department::factory()->create(['manager_user_id' => $manager->id]);
    $member = Member::factory()->for(User::factory())->create(['department_id' => $department->id]);

    expect($manager->can('delete', $member))->toBeFalse();
});
