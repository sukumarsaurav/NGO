<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('logs a user in with correct credentials and redirects per their role', function () {
    $user = User::factory()->create(['password' => Hash::make('CorrectPass123!')]);
    $user->assignRole('donor');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'CorrectPass123!',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/portal');
});

it('rejects an incorrect password without authenticating', function () {
    $user = User::factory()->create(['password' => Hash::make('CorrectPass123!')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'WrongPassword!',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

it('rejects a deactivated (is_active=false) user with correct credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPass123!'),
        'is_active' => false,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'CorrectPass123!',
    ]);

    $this->assertGuest();
    // Same generic message as a wrong password — never confirms the account exists.
    $response->assertSessionHasErrors('email');
});

it('logs a user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
