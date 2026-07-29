<?php

declare(strict_types=1);

use App\Enums\MemberStatus;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('shows the dashboard for a logged-in member, including their member code', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/portal')
        ->assertOk()
        ->assertSee($member->member_code);
});

it('shows a graceful message when a user has no linked member profile', function () {
    $donor = User::factory()->create();
    $donor->assignRole('donor');

    $this->actingAs($donor)
        ->get('/portal')
        ->assertOk()
        ->assertSee('No member profile is linked to this account yet.');
});

it('lets a member view their own profile edit form', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->for($user)->create(['city' => 'Chennai']);

    $this->actingAs($user)
        ->get('/portal/profile')
        ->assertOk()
        ->assertSee('Chennai');
});

it('lets a member update their contact details', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->for($user)->create(['city' => 'Old City']);

    $response = $this->actingAs($user)->put('/portal/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '9999999999',
        'city' => 'New City',
    ]);

    $response->assertRedirect();
    expect($member->fresh()->city)->toBe('New City')
        ->and($user->fresh()->phone)->toBe('9999999999');
});

it('rejects a member trying to change their own status or department via the form', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $department = Department::factory()->create();
    $member = Member::factory()->for($user)->create([
        'status' => MemberStatus::Pending->value,
        'department_id' => $department->id,
    ]);

    // These fields don't even exist in UpdateProfileRequest's validated() output,
    // so posting them is simply ignored, not partially honoured.
    $this->actingAs($user)->put('/portal/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'status' => 'active',
        'department_id' => null,
    ]);

    expect($member->fresh()->status)->toBe(MemberStatus::Pending)
        ->and($member->fresh()->department_id)->toBe($department->id);
});

it('uploads and stores a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->for($user)->create();

    $this->actingAs($user)->put('/portal/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'photo_path' => UploadedFile::fake()->image('photo.jpg'),
    ]);

    $path = $member->fresh()->photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('never lets a member edit another members profile', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    Member::factory()->for($user)->create();

    $otherMember = Member::factory()->for(User::factory())->create(['city' => 'Untouched']);

    // The controller always resolves $request->user()->member — there is no
    // route parameter to substitute another member's ID into, so this is
    // structurally impossible, not just policy-denied. Confirmed here anyway.
    $this->actingAs($user)->put('/portal/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'city' => 'Hijacked',
    ]);

    expect($otherMember->fresh()->city)->toBe('Untouched');
});

it('redirects a guest to login when visiting the portal', function () {
    $this->get('/portal')->assertRedirect('/login');
});
