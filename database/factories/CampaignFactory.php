<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category_id' => CampaignCategory::factory(),
            'title' => rtrim($title, '.'),
            'subtitle' => fake()->sentence(),
            'beneficiary_name' => fake()->name(),
            'story' => fake()->paragraphs(5, true),
            'goal_amount' => fake()->numberBetween(100000, 100000000),
            'raised_amount' => 0,
            'donor_count' => 0,
            'offline_raised_amount' => 0,
            'allows_recurring' => true,
            'is_tax_benefit' => true,
            'is_featured' => false,
            'is_urgent' => false,
            'status' => 'draft',
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'starts_at' => now()->subDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
