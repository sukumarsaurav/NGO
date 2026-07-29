<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as VerifiesEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Every human who can log in — members, donors, and staff all get a row here.
 * See docs/02-DATABASE-SCHEMA.md §2 and docs/modules/M01-foundation-auth.md.
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, VerifiesEmail;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Two Filament panels (`admin`, `manager`) with independent access — a manager
     * is deliberately never permission-gated into `/admin`. See docs/modules/M11-manager-panel.md.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole([UserRole::SuperAdmin->value, UserRole::Admin->value]),
            'manager' => $this->hasRole(UserRole::Manager->value),
            default => false,
        };
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Where this user lands after login, per their highest-privilege role.
     * See docs/modules/M01-foundation-auth.md.
     */
    public function homeRoute(): string
    {
        foreach ([UserRole::SuperAdmin, UserRole::Admin, UserRole::Manager] as $role) {
            if ($this->hasRole($role->value)) {
                return $role->homeRoute();
            }
        }

        return '/portal';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
