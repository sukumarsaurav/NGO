<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Donation;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'provider' => 'razorpay',
            'provider_order_id' => 'order_'.fake()->unique()->bothify('##########'),
            'amount' => fake()->numberBetween(50000, 500000),
            'status' => 'created',
        ];
    }

    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_payment_id' => 'pay_'.fake()->unique()->bothify('##########'),
            'status' => 'captured',
            'fee' => (int) round($attributes['amount'] * 0.02),
            'net_amount' => (int) round($attributes['amount'] * 0.98),
            'captured_at' => now(),
        ]);
    }
}
