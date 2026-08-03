<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'audience' => NoticeAudience::AllMembers->value,
            'priority' => NoticePriority::Normal->value,
            'send_email' => true,
            'status' => NoticeStatus::Draft->value,
            'created_by_user_id' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => NoticeStatus::Sent->value,
            'published_at' => now(),
        ]);
    }
}
