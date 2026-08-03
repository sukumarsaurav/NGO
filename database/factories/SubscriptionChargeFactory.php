<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionCharge>
 */
class SubscriptionChargeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'cycle_number' => 1,
            'amount' => fake()->numberBetween(50000, 500000),
            'status' => 'succeeded',
            'scheduled_for' => now(),
            'charged_at' => now(),
        ];
    }
}
