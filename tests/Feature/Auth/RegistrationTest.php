<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('registers a new user with the donor role and logs them in', function () {
    $response = $this->post('/register', [
        'name' => 'Test Donor',
        'email' => 'donor@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $user = User::where('email', 'donor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->uuid)->not->toBeNull()
        ->and($user->hasRole('donor'))->toBeTrue()
        ->and($user->getAuthPassword())->not->toBe('SecurePass123!');

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/portal');
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'name' => 'Someone Else',
        'email' => 'taken@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $response->assertSessionHasErrors('email');
});

it('rejects registration with a mismatched password confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Test',
        'email' => 'mismatch@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'DoesNotMatch!',
    ]);

    $response->assertSessionHasErrors('password');
    expect(User::where('email', 'mismatch@example.com')->exists())->toBeFalse();
});
