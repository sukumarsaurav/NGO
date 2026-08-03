<?php

declare(strict_types=1);

use App\Actions\Notices\ResolveNoticeAudience;
use App\Enums\NoticeAudience;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Donor;
use App\Models\Member;
use App\Models\User;

it('resolves all_members to only active members', function () {
    $active = Member::factory()->active()->create();
    Member::factory()->create(); // pending — excluded

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::AllMembers, []);

    expect($ids)->toBe([$active->user_id]);
});

it('resolves department audience including nested sub-departments', function () {
    $parent = Department::factory()->create();
    $child = Department::factory()->create(['parent_id' => $parent->id]);

    $inParent = Member::factory()->active()->create(['department_id' => $parent->id]);
    $inChild = Member::factory()->active()->create(['department_id' => $child->id]);
    $elsewhere = Member::factory()->active()->create();

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::Department, ['department_ids' => [$parent->id]]);

    expect($ids)->toContain($inParent->user_id)
        ->toContain($inChild->user_id)
        ->not->toContain($elsewhere->user_id);
});

it('resolves designation audience', function () {
    $designation = Designation::factory()->create();
    $holder = Member::factory()->active()->create(['designation_id' => $designation->id]);
    $other = Member::factory()->active()->create();

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::Designation, ['designation_ids' => [$designation->id]]);

    expect($ids)->toBe([$holder->user_id])->not->toContain($other->user_id);
});

it('resolves specific audience to exactly the given user ids', function () {
    $user = User::factory()->create();

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::Specific, ['user_ids' => [$user->id]]);

    expect($ids)->toBe([$user->id]);
});

it('resolves all_donors to opted-in donors with a linked user account only', function () {
    $optedIn = Donor::factory()->create(['user_id' => User::factory(), 'marketing_opt_in' => true]);
    Donor::factory()->create(['user_id' => User::factory(), 'marketing_opt_in' => false]);
    Donor::factory()->create(['user_id' => null, 'marketing_opt_in' => true]);

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::AllDonors, []);

    expect($ids)->toBe([$optedIn->user_id]);
});

it('deduplicates a user reachable via more than one path', function () {
    $designation = Designation::factory()->create();
    $member = Member::factory()->active()->create(['designation_id' => $designation->id]);

    $ids = app(ResolveNoticeAudience::class)->handle(NoticeAudience::Specific, ['user_ids' => [$member->user_id, $member->user_id]]);

    expect($ids)->toBe([$member->user_id]);
});
