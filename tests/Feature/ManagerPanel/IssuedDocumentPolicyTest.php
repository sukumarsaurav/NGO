<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\IssuedDocument;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DocumentTemplateSeeder::class);
});

it('lets a manager view a document issued to a member in their department', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $department = Department::factory()->create(['manager_user_id' => $manager->id]);
    $member = Member::factory()->create(['department_id' => $department->id]);
    $document = IssuedDocument::factory()->create(['member_id' => $member->id, 'status' => DocumentStatus::Issued->value]);

    expect($manager->can('view', $document))->toBeTrue();
});

it('rejects a manager viewing a document outside their department — the IDOR case', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    Department::factory()->create(['manager_user_id' => $manager->id]);

    $otherDepartment = Department::factory()->create();
    $member = Member::factory()->create(['department_id' => $otherDepartment->id]);
    $document = IssuedDocument::factory()->create(['member_id' => $member->id, 'status' => DocumentStatus::Issued->value]);

    expect($manager->can('view', $document))->toBeFalse();
});

it('lets a manager revoke a document they personally issued, even outside their current department', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $otherDepartment = Department::factory()->create();
    $member = Member::factory()->create(['department_id' => $otherDepartment->id]);
    $document = IssuedDocument::factory()->create([
        'member_id' => $member->id,
        'status' => DocumentStatus::Issued->value,
        'issued_by_user_id' => $manager->id,
    ]);

    expect($manager->can('revoke', $document))->toBeTrue();
});

it('rejects a manager revoking a document neither issued by them nor in their department', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $otherAdmin = User::factory()->create();
    $otherDepartment = Department::factory()->create();
    $member = Member::factory()->create(['department_id' => $otherDepartment->id]);
    $document = IssuedDocument::factory()->create([
        'member_id' => $member->id,
        'status' => DocumentStatus::Issued->value,
        'issued_by_user_id' => $otherAdmin->id,
    ]);

    expect($manager->can('revoke', $document))->toBeFalse();
});
