<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $created_at
 */
class Subscriber extends Model
{
    protected $fillable = ['email', 'name', 'token', 'confirmed_at', 'unsubscribed_at', 'source'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
