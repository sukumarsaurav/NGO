<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // super-admin and admin bypass every permission check. See the seeding
        // rationale in database/seeders/RolePermissionSeeder.php.
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::Admin->value])
                ? true
                : null;
        });
    }
}
