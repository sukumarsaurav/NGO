<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Filament\Admin\Resources\Members\Pages\ListMembers;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('moves selected members to a new department in bulk', function () {
    $oldDept = Department::factory()->create();
    $newDept = Department::factory()->create();

    $members = Member::factory()->count(3)->create(['department_id' => $oldDept->id]);

    Livewire::actingAs($this->admin)
        ->test(ListMembers::class)
        ->callTableBulkAction('changeDepartment', $members, data: ['department_id' => $newDept->id]);

    foreach ($members as $member) {
        expect($member->fresh()->department_id)->toBe($newDept->id);
    }
});

it('changes status in bulk for members where the transition is valid', function () {
    $members = Member::factory()->count(2)->create(['status' => MemberStatus::Pending->value]);

    Livewire::actingAs($this->admin)
        ->test(ListMembers::class)
        ->callTableBulkAction('changeStatus', $members, data: ['status' => MemberStatus::Active->value]);

    foreach ($members as $member) {
        expect($member->fresh()->status)->toBe(MemberStatus::Active);
    }
});

it('skips members for whom the bulk status transition is invalid, without erroring the whole batch', function () {
    $eligible = Member::factory()->create(['status' => MemberStatus::Pending->value]);
    $ineligible = Member::factory()->create(['status' => MemberStatus::Resigned->value]);

    Livewire::actingAs($this->admin)
        ->test(ListMembers::class)
        ->callTableBulkAction('changeStatus', collect([$eligible, $ineligible]), data: ['status' => MemberStatus::Active->value]);

    expect($eligible->fresh()->status)->toBe(MemberStatus::Active)
        ->and($ineligible->fresh()->status)->toBe(MemberStatus::Resigned);
});
