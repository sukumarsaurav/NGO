<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Members\Pages;

use App\Actions\Members\UpdateMember as UpdateMemberAction;
use App\Filament\Manager\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
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
        $departmentId = (int) ($data['department_id'] ?? 0);

        if (! in_array($departmentId, Auth::user()->managedDepartmentIds(), true)) {
            throw ValidationException::withMessages([
                'department_id' => 'You can only move a member to a department you manage.',
            ]);
        }

        $user = $data['user'] ?? [];

        return app(UpdateMemberAction::class)->handle($record, [
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'phone' => $user['phone'] ?? null,
            ...collect($data)->except(['user', 'status', 'designation_id', 'member_code'])->all(),
        ]);
    }
}
