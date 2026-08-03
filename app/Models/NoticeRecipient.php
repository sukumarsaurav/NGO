<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $read_at
 * @property Carbon|null $emailed_at
 */
class NoticeRecipient extends Model
{
    use HasFactory;

    protected $fillable = ['notice_id', 'user_id', 'read_at', 'email_status', 'emailed_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Notice, $this>
     */
    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
