<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImpactStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImpactStat>
 */
class ImpactStatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->words(2, true),
            'value' => (string) fake()->numberBetween(100, 9999),
            'suffix' => '+',
            'sort_order' => 0,
        ];
    }
}
