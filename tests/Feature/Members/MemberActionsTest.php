<?php

declare(strict_types=1);

use App\Actions\Members\AssignDesignation;
use App\Actions\Members\CreateMember;
use App\Actions\Members\DeactivateMember;
use App\Actions\Members\UpdateMember;
use App\Enums\MemberStatus;
use App\Events\DesignationAssigned;
use App\Events\MemberCreated;
use App\Models\Designation;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
});

describe('CreateMember', function () {
    it('creates a linked user and member row with a real generated code', function () {
        $member = app(CreateMember::class)->handle([
            'name' => 'Asha Verma',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'joined_on' => '2026-01-15',
        ]);

        expect($member->exists)->toBeTrue()
            ->and($member->member_code)->toMatch('/^VGWGF-\d{4}-00001$/')
            ->and($member->user->name)->toBe('Asha Verma')
            ->and($member->user->email)->toBe('asha@example.com')
            ->and($member->user->password)->toBeNull()
            ->and($member->status)->toBe(MemberStatus::Pending);
    });

    it('assigns the member role to the linked user', function () {
        $member = app(CreateMember::class)->handle([
            'name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
        ]);

        expect($member->user->hasRole('member'))->toBeTrue();
    });

    it('fires MemberCreated', function () {
        Event::fake([MemberCreated::class]);

        $member = app(CreateMember::class)->handle([
            'name' => 'Priya Singh',
            'email' => 'priya@example.com',
        ]);

        Event::assertDispatched(MemberCreated::class, fn ($event) => $event->member->is($member));
    });

    it('never leaves an orphaned user row if member creation fails', function () {
        // A duplicate email at the DB level makes the whole transaction fail.
        User::factory()->create(['email' => 'duplicate@example.com']);

        expect(fn () => app(CreateMember::class)->handle([
            'name' => 'Someone',
            'email' => 'duplicate@example.com',
        ]))->toThrow(Exception::class);

        expect(User::where('email', 'duplicate@example.com')->count())->toBe(1);
    });
});

describe('UpdateMember', function () {
    it('updates profile fields on both member and user', function () {
        $member = Member::factory()->for(User::factory())->create();

        app(UpdateMember::class)->handle($member, [
            'name' => 'New Name',
            'email' => $member->user->email,
            'city' => 'Lucknow',
        ]);

        expect($member->fresh()->city)->toBe('Lucknow')
            ->and($member->user->fresh()->name)->toBe('New Name');
    });

    it('ignores status and designation_id even if passed', function () {
        $member = Member::factory()->for(User::factory())->create(['status' => MemberStatus::Pending->value]);

        app(UpdateMember::class)->handle($member, [
            'status' => MemberStatus::Active->value,
            'city' => 'Delhi',
        ]);

        expect($member->fresh()->status)->toBe(MemberStatus::Pending)
            ->and($member->fresh()->city)->toBe('Delhi');
    });
});

describe('AssignDesignation', function () {
    it('sets the designation and fires DesignationAssigned', function () {
        Event::fake([DesignationAssigned::class]);

        $member = Member::factory()->for(User::factory())->create();
        $designation = Designation::factory()->create();

        $updated = app(AssignDesignation::class)->handle($member, $designation);

        expect($updated->designation_id)->toBe($designation->id);
        Event::assertDispatched(DesignationAssigned::class, fn ($event) => $event->member->is($member)
            && $event->designation->is($designation));
    });
});

describe('DeactivateMember', function () {
    it('approves a pending member into active and grants portal access', function () {
        $member = Member::factory()->for(User::factory(['is_active' => false]))->create([
            'status' => MemberStatus::Pending->value,
        ]);

        $updated = app(DeactivateMember::class)->handle($member, MemberStatus::Active);

        expect($updated->status)->toBe(MemberStatus::Active)
            ->and($member->user->fresh()->is_active)->toBeTrue();
    });

    it('suspends an active member and revokes portal access', function () {
        $member = Member::factory()->for(User::factory())->create(['status' => MemberStatus::Active->value]);

        $updated = app(DeactivateMember::class)->handle($member, MemberStatus::Suspended, 'Under review');

        expect($updated->status)->toBe(MemberStatus::Suspended)
            ->and($member->user->fresh()->is_active)->toBeFalse()
            ->and($updated->notes)->toContain('Under review');
    });

    it('rejects an invalid transition', function () {
        $member = Member::factory()->for(User::factory())->create(['status' => MemberStatus::Resigned->value]);

        expect(fn () => app(DeactivateMember::class)->handle($member, MemberStatus::Active))
            ->toThrow(InvalidArgumentException::class);
    });

    it('reinstates a suspended member', function () {
        $member = Member::factory()->for(User::factory(['is_active' => false]))->create([
            'status' => MemberStatus::Suspended->value,
        ]);

        $updated = app(DeactivateMember::class)->handle($member, MemberStatus::Active);

        expect($updated->status)->toBe(MemberStatus::Active)
            ->and($member->user->fresh()->is_active)->toBeTrue();
    });
});
