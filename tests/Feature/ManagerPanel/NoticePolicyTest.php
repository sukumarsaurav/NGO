<?php

declare(strict_types=1);

use App\Enums\NoticeAudience;
use App\Models\Department;
use App\Models\Notice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets a manager view a notice they created', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $notice = Notice::factory()->create(['created_by_user_id' => $manager->id]);

    expect($manager->can('view', $notice))->toBeTrue();
});

it('lets a manager view a department-targeted notice aimed at a department they manage', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $department = Department::factory()->create(['manager_user_id' => $manager->id]);

    $otherCreator = User::factory()->create();
    $notice = Notice::factory()->create([
        'created_by_user_id' => $otherCreator->id,
        'audience' => NoticeAudience::Department->value,
        'audience_filter' => ['department_ids' => [$department->id]],
    ]);

    expect($manager->can('view', $notice))->toBeTrue();
});

it('rejects a manager viewing a department-targeted notice for a department they do not manage — the IDOR case', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    Department::factory()->create(['manager_user_id' => $manager->id]);

    $otherDepartment = Department::factory()->create();
    $otherCreator = User::factory()->create();
    $notice = Notice::factory()->create([
        'created_by_user_id' => $otherCreator->id,
        'audience' => NoticeAudience::Department->value,
        'audience_filter' => ['department_ids' => [$otherDepartment->id]],
    ]);

    expect($manager->can('view', $notice))->toBeFalse();
});

it('rejects a manager viewing an all_members notice created by someone else', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    Department::factory()->create(['manager_user_id' => $manager->id]);

    $admin = User::factory()->create();
    $notice = Notice::factory()->create([
        'created_by_user_id' => $admin->id,
        'audience' => NoticeAudience::AllMembers->value,
    ]);

    expect($manager->can('view', $notice))->toBeFalse();
});

it('never lets a manager edit or delete a notice someone else created', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $department = Department::factory()->create(['manager_user_id' => $manager->id]);

    $otherCreator = User::factory()->create();
    $notice = Notice::factory()->create([
        'created_by_user_id' => $otherCreator->id,
        'audience' => NoticeAudience::Department->value,
        'audience_filter' => ['department_ids' => [$department->id]],
    ]);

    expect($manager->can('update', $notice))->toBeFalse()
        ->and($manager->can('delete', $notice))->toBeFalse();
});
