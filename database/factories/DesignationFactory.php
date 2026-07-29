<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->randomElement([
            'President', 'Secretary', 'Treasurer', 'Volunteer Coordinator',
            'Field Officer', 'Programme Manager', 'Trustee', 'Executive Member',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'rank' => fake()->numberBetween(0, 100),
            'letter_template_id' => null,
            'is_active' => true,
        ];
    }
}
