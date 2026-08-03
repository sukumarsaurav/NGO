<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Members\Pages;

use App\Actions\Members\CreateMember as CreateMemberAction;
use App\Filament\Manager\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Same nested-`user`-array handling as the Admin `CreateMember` page (see
 * that class's docblock). The one addition: `department_id` is re-validated
 * server-side against `managedDepartmentIds()` — the form's Select already
 * only offers those departments, but a manager could still post an
 * arbitrary ID directly. See docs/modules/M11-manager-panel.md: "the server
 * validates it again on submit."
 */
class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function handleRecordCreation(array $data): Member
    {
        $departmentId = (int) ($data['department_id'] ?? 0);

        if (! in_array($departmentId, Auth::user()->managedDepartmentIds(), true)) {
            throw ValidationException::withMessages([
                'department_id' => 'You can only add members to a department you manage.',
            ]);
        }

        $user = $data['user'] ?? [];

        return app(CreateMemberAction::class)->handle([
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'phone' => $user['phone'] ?? null,
            'department_id' => $departmentId,
            'designation_id' => $data['designation_id'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'id_proof_type' => $data['id_proof_type'] ?? null,
            'id_proof_number' => $data['id_proof_number'] ?? null,
            'joined_on' => $data['joined_on'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
