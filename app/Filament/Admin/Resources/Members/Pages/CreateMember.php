<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Actions\Members\CreateMember as CreateMemberAction;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

/**
 * Bypasses Filament's default Eloquent::create() entirely — creating a member
 * means creating a linked `users` row first (with a generated member code and
 * the `member` role), which is what App\Actions\Members\CreateMember does.
 * Filament's automatic relationship-dot-notation saving is designed for
 * attaching to an EXISTING related record, not for creating a new BelongsTo
 * parent inline — verified against the real form state before writing this,
 * not assumed. `data.user` arrives as a nested array (see MemberForm's
 * `user.name` / `user.email` / `user.phone` fields) and is flattened here.
 */
class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function handleRecordCreation(array $data): Member
    {
        $user = $data['user'] ?? [];

        return app(CreateMemberAction::class)->handle([
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'phone' => $user['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
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
