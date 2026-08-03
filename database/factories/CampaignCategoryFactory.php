<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CampaignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignCategory>
 */
class CampaignCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'intro_body' => fake()->paragraphs(3, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
