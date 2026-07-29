<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Mirrors the five roles seeded by RolePermissionSeeder (docs/00-PROJECT-OVERVIEW.md).
 * The string values are the exact role names registered with spatie/laravel-permission.
 */
enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';
    case Donor = 'donor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Member => 'Member',
            self::Donor => 'Donor',
        };
    }

    /**
     * Where a user with only this role lands immediately after login.
     * See docs/modules/M01-foundation-auth.md.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => '/admin',
            self::Manager => '/manager',
            self::Member, self::Donor => '/portal',
        };
    }
}
