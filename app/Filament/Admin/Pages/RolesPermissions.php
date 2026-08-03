<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

/**
 * `super-admin` only — see docs/modules/M11-manager-panel.md's "Permission
 * management UI": a role/permission matrix. super-admin and admin bypass
 * every check via Gate::before regardless of what's ticked here (see
 * RolePermissionSeeder) — the matrix is only load-bearing for `manager`
 * (and any future custom role), which is called out in the view.
 *
 * Not a resource: there's no single "role/permission" record being
 * edited, it's a many-to-many grid across every role and every permission.
 */
class RolesPermissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static string|UnitEnum|null $navigationGroup = 'Access';

    protected string $view = 'filament.admin.pages.roles-permissions';

    /** @var array<string, list<int>> role name => granted permission ids */
    public array $grants = [];

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('manage_roles') ?? false;
    }

    public function mount(): void
    {
        foreach ($this->roles() as $role) {
            $this->grants[$role->name] = $role->permissions()->pluck('id')->all();
        }
    }

    /**
     * @return Collection<int, Role>
     */
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions()
    {
        return Permission::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $before = [];

        foreach ($this->roles() as $role) {
            $before[$role->name] = $role->permissions()->pluck('name')->sort()->values()->all();
        }

        foreach ($this->roles() as $role) {
            $role->syncPermissions($this->grants[$role->name] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $affectedUserIds = [];

        foreach ($this->roles() as $role) {
            $after = $role->permissions()->pluck('name')->sort()->values()->all();

            if ($after === ($before[$role->name] ?? [])) {
                continue;
            }

            activity('roles')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties(['old' => $before[$role->name] ?? [], 'new' => $after])
                ->log("Permissions updated for role '{$role->name}'");

            $affectedUserIds = [...$affectedUserIds, ...$role->users()->pluck('id')->all()];
        }

        if ($affectedUserIds !== []) {
            // Force a session refresh for everyone holding an affected role —
            // see docs/modules/M11-manager-panel.md: "apply immediately
            // rather than at next login."
            DB::table('sessions')->whereIn('user_id', array_unique($affectedUserIds))->delete();
        }

        Notification::make()->title('Permissions updated')->success()->send();
    }
}
