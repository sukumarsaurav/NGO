<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

/**
 * `super-admin` only — see docs/modules/M11-manager-panel.md's "Permission
 * management UI": role assignment plus a per-user permission override for
 * exceptions. `roles`/`permissions` are Spatie's own relations; binding them
 * with `->relationship()` syncs the pivot tables automatically on save.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(150),
            TextInput::make('email')->email()->required()->maxLength(190),
            TextInput::make('phone')->tel()->maxLength(20),
            Toggle::make('is_active')->default(true),

            Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->helperText('super-admin and admin bypass every permission check regardless of what is granted below.'),

            CheckboxList::make('permissions')
                ->relationship('permissions', 'name')
                ->options(fn () => Permission::query()->pluck('name', 'id'))
                ->columns(3)
                ->helperText('Per-user overrides, on top of anything granted by role.'),
        ]);
    }
}
