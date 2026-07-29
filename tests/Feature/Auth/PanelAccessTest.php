<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets an admin log in and see the admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('lets a super-admin log in and see the admin dashboard', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get('/admin')
        ->assertOk();
});

it('rejects a member-role user from the admin panel with 403', function () {
    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($member)
        ->get('/admin')
        ->assertForbidden();
});

it('rejects a donor-role user from the admin panel with 403', function () {
    $donor = User::factory()->create();
    $donor->assignRole('donor');

    $this->actingAs($donor)
        ->get('/admin')
        ->assertForbidden();
});

it('lets a manager log in and see the manager panel, but not /admin', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)->get('/manager')->assertOk();
    $this->actingAs($manager)->get('/admin')->assertForbidden();
});

it('resolves the correct home route per role', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');
    expect($superAdmin->homeRoute())->toBe('/admin');

    $manager = User::factory()->create();
    $manager->assignRole('manager');
    expect($manager->homeRoute())->toBe('/manager');

    $member = User::factory()->create();
    $member->assignRole('member');
    expect($member->homeRoute())->toBe('/portal');
});
