<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Donation;
use App\Models\Donor;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(50000, 500000);

        return [
            'donor_id' => Donor::factory(),
            'amount' => $amount,
            'items_amount' => 0,
            'free_amount' => $amount,
            'currency' => 'INR',
            'type' => 'one_time',
            'status' => 'pending',
            'is_offline' => false,
            'financial_year' => FinancialYear::current()->toString(),
            'eligible_for_80g' => true,
        ];
    }

    public function succeeded(): static
    {
        return $this->state([
            'status' => 'succeeded',
            'donated_at' => now(),
            'donation_number' => fake()->unique()->numerify('DN-2026-27-######'),
        ]);
    }
}
