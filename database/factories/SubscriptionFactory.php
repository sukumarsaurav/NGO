<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donor_id' => Donor::factory(),
            'provider' => 'razorpay',
            'provider_subscription_id' => 'sub_'.fake()->unique()->bothify('##########'),
            'amount' => fake()->numberBetween(50000, 500000),
            'interval' => 'monthly',
            'total_cycles' => null,
            'completed_cycles' => 0,
            'status' => 'pending_authentication',
            'mandate_type' => 'upi_autopay',
            'failed_charge_count' => 0,
            'total_collected' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'started_at' => now(),
            'next_charge_at' => now()->addMonthNoOverflow(),
        ]);
    }
}
