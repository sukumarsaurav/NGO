<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Faker-unique, not MemberCodeGenerator — factories shouldn't take
            // a dependency on a stateful row-locked service just to seed a
            // uniqueness constraint. Real member creation goes through
            // CreateMember, which does use the generator.
            'member_code' => fake()->unique()->numerify('VGWGF-2026-#####'),
            'department_id' => null,
            'designation_id' => null,
            'photo_path' => null,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->numerify('######'),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('##########'),
            'id_proof_type' => 'Aadhaar',
            'id_proof_number' => fake()->numerify('############'),
            'joined_on' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'valid_until' => null,
            'status' => MemberStatus::Pending->value,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => MemberStatus::Active->value]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => MemberStatus::Suspended->value]);
    }
}
