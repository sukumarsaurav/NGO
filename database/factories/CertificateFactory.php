<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => '12A Registration Certificate',
            'description' => fake()->sentence(),
            'issuing_authority' => 'Income Tax Department',
            'file_path' => 'certificates/'.fake()->uuid().'.pdf',
            'valid_from' => null,
            'valid_until' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
