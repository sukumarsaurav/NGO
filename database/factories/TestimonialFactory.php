<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'location' => fake()->city(),
            'quote' => fake()->paragraph(),
            'rating' => 5,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
