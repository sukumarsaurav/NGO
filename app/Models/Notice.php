<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * See docs/02-DATABASE-SCHEMA.md §9 and docs/modules/M09-notices-communication.md.
 *
 * @property NoticeAudience $audience
 * @property array<string, mixed>|null $audience_filter
 * @property NoticePriority $priority
 * @property NoticeStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 */
class Notice extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'title', 'body', 'attachment_path', 'audience', 'audience_filter',
        'priority', 'send_email', 'published_at', 'expires_at', 'status',
        'recipient_count', 'read_count', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'audience' => NoticeAudience::class,
            'audience_filter' => 'array',
            'priority' => NoticePriority::class,
            'status' => NoticeStatus::class,
            'send_email' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<NoticeRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(NoticeRecipient::class);
    }
}
