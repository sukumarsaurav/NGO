<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(DocumentType::cases())->value,
            'body_html' => '<p>{{ $member->name }} — {{ $member->member_code }}</p>',
            'css' => null,
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'background_path' => null,
            'qr_enabled' => true,
            'qr_position' => ['x' => 5, 'y' => 5, 'size' => 20],
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function idCard(): static
    {
        return $this->state([
            'type' => DocumentType::IdCard->value,
            'page_size' => 'CR80',
            'orientation' => 'landscape',
        ]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
