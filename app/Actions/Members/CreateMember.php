<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Enums\MemberStatus;
use App\Enums\UserRole;
use App\Events\MemberCreated;
use App\Models\Member;
use App\Models\User;
use App\Services\Numbering\MemberCodeGenerator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

/**
 * Creates `users` + `members` rows in one transaction, generates the member
 * code, assigns the `member` role, fires MemberCreated.
 * See docs/modules/M03-members.md.
 */
final class CreateMember
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly MemberCodeGenerator $memberCodeGenerator,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Pre-validated. Requires: name, email.
     *                                      Optional: phone, department_id, designation_id, photo_path,
     *                                      date_of_birth, gender, blood_group, address_line1/2, city, state,
     *                                      pincode, emergency_contact_name/phone, id_proof_type, id_proof_number,
     *                                      joined_on (defaults to today), status (defaults to pending), notes.
     */
    public function handle(array $data): Member
    {
        return $this->db->transaction(function () use ($data) {
            // Null password: the member claims the account via the password-
            // reset flow, same lazy-claim pattern as a guest donor (M01).
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => null,
            ]);

            $user->assignRole(UserRole::Member->value);

            $member = Member::query()->create([
                'user_id' => $user->id,
                'member_code' => $this->memberCodeGenerator->next(),
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
                'status' => $data['status'] ?? MemberStatus::Pending->value,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->events->dispatch(new MemberCreated($member));

            return $member;
        });
    }
}
