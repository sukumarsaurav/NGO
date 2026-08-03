<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Sprint 15 hardening — see docs/03-ROADMAP.md's "Rate limit: donation
 * endpoint, login, password reset, webhook". Login already had a throttle;
 * `reset-password` (the actual submission, not the "email me a link" step)
 * didn't and was a token-brute-forcing gap.
 */
it('throttles repeated login attempts', function () {
    $user = User::factory()->create(['password' => Hash::make('CorrectPass123!')]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertStatus(429);
});

it('throttles repeated reset-password submissions', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'nobody@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
    }

    $this->post('/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'nobody@example.com',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(429);
});
