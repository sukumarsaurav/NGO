<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton so all() is memoized in-memory for the lifetime of a
        // single request, on top of the forever-cache it wraps.
        $this->app->singleton(SettingsRepository::class);
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

        // Conservative default for shared-hosting SMTP accounts — see
        // App\Jobs\SendNoticeEmail and docs/modules/M09-notices-communication.md's
        // "SMTP rate limit hit" edge case.
        RateLimiter::for('notice-emails', fn () => Limit::perMinute(50));
    }
}
