<?php

declare(strict_types=1);

use App\Filament\Admin\Pages\RolesPermissions;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('changing a users roles evicts their active sessions', function () {
    $target = User::factory()->create();
    $target->assignRole('member');

    DB::table('sessions')->insert([
        'id' => 'test-session-id',
        'user_id' => $target->id,
        'payload' => base64_encode('x'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['roles' => [Role::where('name', 'manager')->first()->id]])
        ->call('save');

    expect(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse()
        ->and($target->fresh()->hasRole('manager'))->toBeTrue();
});

it('logs the role change to activity_log', function () {
    $target = User::factory()->create();
    $target->assignRole('member');

    Livewire::actingAs($this->admin)
        ->test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['roles' => [Role::where('name', 'manager')->first()->id]])
        ->call('save');

    expect(Activity::where('log_name', 'users')->latest('id')->first()?->subject_id)->toBe($target->id);
});

it('updating the permission matrix syncs role permissions and evicts affected sessions', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    DB::table('sessions')->insert([
        'id' => 'manager-session-id',
        'user_id' => $manager->id,
        'payload' => base64_encode('x'),
        'last_activity' => now()->timestamp,
    ]);

    $extraPermission = Permission::where('name', 'manage_pages')->first();
    $managerRole = Role::where('name', 'manager')->first();
    $currentIds = $managerRole->permissions()->pluck('id')->all();

    Livewire::actingAs($this->admin)
        ->test(RolesPermissions::class)
        ->set('grants.manager', [...$currentIds, $extraPermission->id])
        ->call('save');

    expect($managerRole->fresh()->hasPermissionTo('manage_pages'))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $manager->id)->exists())->toBeFalse();
});
