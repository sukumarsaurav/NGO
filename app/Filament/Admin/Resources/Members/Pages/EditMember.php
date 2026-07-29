<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Actions\Members\UpdateMember as UpdateMemberAction;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * The `user.name` / `user.email` / `user.phone` fields do NOT auto-hydrate
 * from the related User on edit — verified against the real component state
 * before writing this (they came back null despite a real linked user with
 * data). mutateFormDataBeforeFill() below fills them explicitly. Saving goes
 * through App\Actions\Members\UpdateMember, which deliberately excludes
 * status and designation_id — those change via their own actions, not a
 * generic form save. See docs/modules/M03-members.md.
 */
class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        // No delete/restore actions — MemberPolicy always denies delete/restore
        // except via the Gate::before super-admin/admin bypass, and members are
        // deactivated via status transitions (DeactivateMember), not deleted.
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Member $member */
        $member = $this->getRecord();

        $data['user'] = [
            'name' => $member->user->name,
            'email' => $member->user->email,
            'phone' => $member->user->phone,
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Member $record */
        $user = $data['user'] ?? [];

        return app(UpdateMemberAction::class)->handle($record, [
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'phone' => $user['phone'] ?? null,
            ...collect($data)->except(['user', 'status', 'designation_id', 'member_code'])->all(),
        ]);
    }
}
