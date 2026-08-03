<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignProduct>
 */
class CampaignProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->unique()->words(2, true),
            'unit_price' => fake()->numberBetween(20000, 200000),
            'units_needed' => fake()->numberBetween(10, 1000),
            'units_funded' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
