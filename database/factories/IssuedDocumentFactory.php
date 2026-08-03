<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentTemplate;
use App\Models\IssuedDocument;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IssuedDocument>
 */
class IssuedDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'document_number' => fake()->unique()->numerify('AL-2026-####'),
            'type' => 'appointment_letter',
            'member_id' => Member::factory(),
            'template_id' => DocumentTemplate::factory(),
            'title' => fake()->sentence(3),
            'snapshot_data' => ['name' => fake()->name(), 'member_code' => 'VGWGF-2026-00001'],
            'file_path' => null,
            'qr_payload' => fake()->url(),
            'issued_by_user_id' => User::factory(),
            'issued_on' => now()->toDateString(),
            'valid_until' => null,
            'status' => 'queued',
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'file_path' => "documents/{$attributes['uuid']}.pdf",
        ]);
    }
}
