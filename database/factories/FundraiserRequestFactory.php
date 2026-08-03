<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FundraiserRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundraiserRequest>
 */
class FundraiserRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(3, true),
            'goal_amount' => fake()->numberBetween(100000, 10000000),
            'status' => 'new',
        ];
    }
}
