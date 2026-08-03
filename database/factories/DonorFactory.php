<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donor>
 */
class DonorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'pan' => null,
            'donor_type' => 'individual',
            'total_donated' => 0,
            'donation_count' => 0,
            'is_anonymous' => false,
            'marketing_opt_in' => false,
        ];
    }
}
