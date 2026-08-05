<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo_path' => 'partners/'.fake()->uuid().'.png',
            'website_url' => fake()->url(),
            'category' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
