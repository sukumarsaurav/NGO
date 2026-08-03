<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notice;
use App\Models\NoticeRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoticeRecipient>
 */
class NoticeRecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notice_id' => Notice::factory(),
            'user_id' => User::factory(),
            'email_status' => 'pending',
        ];
    }
}
