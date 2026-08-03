<?php

declare(strict_types=1);

use App\Filament\Manager\Pages\Reports as ManagerReports;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Services\Reports\ReportQueries;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->manager = User::factory()->create();
    $this->manager->assignRole('manager');
    $this->department = Department::factory()->create(['manager_user_id' => $this->manager->id]);
    $this->otherDepartment = Department::factory()->create();
});

it('scopes the manager member roster report to only the managers department', function () {
    $inDept = Member::factory()->create(['department_id' => $this->department->id]);
    Member::factory()->create(['department_id' => $this->otherDepartment->id]);

    Livewire::actingAs($this->manager)
        ->test(ManagerReports::class)
        ->call('selectReport', 'member_roster')
        ->assertSee($inDept->member_code);

    $ids = app(ReportQueries::class)
        ->memberRoster($this->manager->managedDepartmentIds())
        ->pluck('id');

    expect($ids)->toContain($inDept->id);
});

it('never lets a manager reach the admin reports page', function () {
    $this->actingAs($this->manager)->get('/admin/reports')->assertForbidden();
});
