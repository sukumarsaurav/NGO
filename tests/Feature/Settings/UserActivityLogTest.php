<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs a user attribute change under the users log', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $user->update(['name' => 'Updated Name']);

    // LogsActivity also logs the creation itself — scope to the update.
    $activity = Activity::query()
        ->where('log_name', 'users')
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->changes()['attributes']['name'])->toBe('Updated Name')
        ->and($activity->changes()['old']['name'])->toBe('Original Name');
});

it('never logs the password even when it changes', function () {
    $user = User::factory()->create();

    $user->update(['password' => bcrypt('a-new-password')]);

    // dontSubmitEmptyLogs() means a password-only change (not in the
    // allow-list) produces no *update* entry — only the earlier creation log exists.
    $updateActivity = Activity::query()
        ->where('log_name', 'users')
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->first();

    expect($updateActivity)->toBeNull();
});

it('does not log a save that touches no allow-listed attribute', function () {
    $user = User::factory()->create();

    $user->update(['last_login_at' => now()]);

    $updateActivity = Activity::query()
        ->where('log_name', 'users')
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->first();

    expect($updateActivity)->toBeNull();
});
