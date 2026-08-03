<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2, false),
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(),
            'body_html' => '<p>Hi {{ name }},</p>',
            'available_variables' => ['name'],
            'is_active' => true,
            'send_copy_to_admin' => false,
        ];
    }
}
