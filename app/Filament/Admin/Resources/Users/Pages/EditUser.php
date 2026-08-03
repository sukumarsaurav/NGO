<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

/**
 * Role/permission changes are the security-sensitive part of this form —
 * see docs/modules/M11-manager-panel.md: "Every change is written to
 * activity_log" and "Changing a user's roles should force a session
 * refresh so the new permissions apply immediately rather than at next
 * login." `LogsActivity` on `User` already covers the plain-column diff;
 * `roles`/`permissions` are separate pivot tables it doesn't see, so this
 * page snapshots and logs them itself, then evicts the target user's
 * database sessions when either changed.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var list<string> */
    private array $rolesBeforeSave = [];

    /** @var list<string> */
    private array $permissionsBeforeSave = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function beforeSave(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        $this->rolesBeforeSave = $user->roles()->pluck('name')->sort()->values()->all();
        $this->permissionsBeforeSave = $user->permissions()->pluck('name')->sort()->values()->all();
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->getRecord()->fresh();

        $rolesAfter = $user->roles()->pluck('name')->sort()->values()->all();
        $permissionsAfter = $user->permissions()->pluck('name')->sort()->values()->all();

        if ($rolesAfter === $this->rolesBeforeSave && $permissionsAfter === $this->permissionsBeforeSave) {
            return;
        }

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'roles' => ['old' => $this->rolesBeforeSave, 'new' => $rolesAfter],
                'permissions' => ['old' => $this->permissionsBeforeSave, 'new' => $permissionsAfter],
            ])
            ->log("Roles/permissions updated for '{$user->name}'");

        // Force a session refresh: the target user's next request re-hydrates
        // Gate/permission checks against their new roles instead of whatever
        // was cached in their existing session.
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
