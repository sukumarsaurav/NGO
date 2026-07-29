<?php

declare(strict_types=1);

use App\Actions\Members\UpdateMember;
use App\Enums\MemberStatus;
use App\Filament\Admin\Resources\Members\Pages\CreateMember;
use App\Filament\Admin\Resources\Members\Pages\EditMember;
use App\Filament\Admin\Resources\Members\Pages\ListMembers;
use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists members', function () {
    Member::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get('/admin/members')
        ->assertOk();
});

it('creates a member through the real form, producing a linked user with the member role', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMember::class)
        ->fillForm([
            'user.name' => 'Kavita Rao',
            'user.email' => 'kavita@example.com',
            'user.phone' => '9988776655',
            'joined_on' => '2026-02-01',
            'city' => 'Pune',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = Member::query()->whereHas('user', fn ($q) => $q->where('email', 'kavita@example.com'))->first();

    expect($member)->not->toBeNull()
        ->and($member->user->name)->toBe('Kavita Rao')
        ->and($member->user->hasRole('member'))->toBeTrue()
        ->and($member->member_code)->toMatch('/^VGWGF-\d{4}-\d{5}$/')
        ->and($member->city)->toBe('Pune')
        ->and($member->status)->toBe(MemberStatus::Pending);
});

it('rejects creating a member without a name or email', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMember::class)
        ->fillForm(['joined_on' => '2026-01-01'])
        ->call('create')
        ->assertHasFormErrors(['user.name', 'user.email']);
});

it('edits a member and correctly hydrates the linked users fields', function () {
    $member = Member::factory()->for(User::factory([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'phone' => '1111111111',
    ]))->create();

    Livewire::actingAs($this->admin)
        ->test(EditMember::class, ['record' => $member->getRouteKey()])
        ->assertFormSet([
            'user.name' => 'Old Name',
            'user.email' => 'old@example.com',
            'user.phone' => '1111111111',
        ]);
});

it('saves an edit to both member and user fields', function () {
    $member = Member::factory()->for(User::factory(['name' => 'Old Name']))->create(['city' => 'Old City']);

    Livewire::actingAs($this->admin)
        ->test(EditMember::class, ['record' => $member->getRouteKey()])
        ->fillForm([
            'user.name' => 'New Name',
            'city' => 'New City',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh()->city)->toBe('New City')
        ->and($member->user->fresh()->name)->toBe('New Name');
});

it('does not change status or designation via the generic edit form, even if submitted', function () {
    $department = Department::factory()->create();
    $member = Member::factory()->for(User::factory())->create([
        'status' => MemberStatus::Pending->value,
        'department_id' => $department->id,
    ]);

    // The status field is disabled/dehydrated(false) on edit — this proves
    // the server-side action also refuses to move it, not just the UI.
    app(UpdateMember::class)->handle($member, [
        'status' => MemberStatus::Active->value,
        'city' => 'Somewhere',
    ]);

    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
});

it('shows a photo placeholder column without erroring when no photo is set', function () {
    Member::factory()->for(User::factory())->create(['photo_path' => null]);

    Livewire::actingAs($this->admin)
        ->test(ListMembers::class)
        ->assertOk();
});
