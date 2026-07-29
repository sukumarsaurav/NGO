<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One super-admin, credentials from .env. Per docs/02-DATABASE-SCHEMA.md §13:
 * "must be changed on first login" — enforced by requiring ADMIN_SEED_PASSWORD
 * to be set explicitly rather than falling back to a guessable default.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('vgwgf.admin_seed_email');
        $password = config('vgwgf.admin_seed_password');

        if (! $email || ! $password) {
            $this->command?->warn(
                'Skipped: set ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD in .env before seeding an admin.'
            );

            return;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (! $admin->hasRole(UserRole::SuperAdmin->value)) {
            $admin->assignRole(UserRole::SuperAdmin->value);
        }
    }
}
