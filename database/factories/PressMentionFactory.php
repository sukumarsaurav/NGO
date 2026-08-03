<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PressMention;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PressMention>
 */
class PressMentionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'outlet_name' => fake()->company(),
            'url' => fake()->url(),
            'published_on' => fake()->date(),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
