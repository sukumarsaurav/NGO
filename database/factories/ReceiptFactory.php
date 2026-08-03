<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receipt_number' => fake()->unique()->numerify('VGWGF/RCP/2026-27/#####'),
            'sequence_number' => fake()->unique()->numberBetween(1, 100000),
            'series' => 'donation',
            'revision' => 1,
            'donation_id' => Donation::factory(),
            'donor_id' => Donor::factory(),
            'financial_year' => '2026-27',
            'amount' => fake()->numberBetween(50000, 500000),
            'amount_in_words' => 'Five Hundred Rupees Only',
            'snapshot_data' => ['donor_name' => fake()->name()],
            'issued_on' => now()->toDateString(),
            'email_status' => 'pending',
        ];
    }
}
