<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('never lets a manager reach the admin panel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)->get('/admin')->assertForbidden();
});

it('lets a manager reach their own panel', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)->get('/manager')->assertSuccessful();
});

it('never lets an admin reach the manager panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/manager')->assertForbidden();
});

it('lets a manager view the donations list but denies the settings and receipts permissions', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    expect($manager->can('view_donations'))->toBeTrue()
        ->and($manager->can('manage_settings'))->toBeFalse()
        ->and($manager->can('view_receipts'))->toBeFalse()
        ->and($manager->can('refund_donations'))->toBeFalse();
});
