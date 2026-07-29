<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * NOTE: deliberately does NOT use WithoutModelEvents. User::booted() relies on
 * the `creating` event to generate `uuid` (NOT NULL UNIQUE) — that trait
 * suppresses model events for every seeder called from here, which would leave
 * every seeded user with a null uuid and fail the unique constraint.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
