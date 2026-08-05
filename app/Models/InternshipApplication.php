<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InternshipApplicationStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A candidate's submission from the public Internship page. `resume_path`
 * lives on the `local` disk (private) — resumes carry PII and must never be
 * publicly linkable, unlike Certificate's public file_path.
 *
 * @property InternshipApplicationStatus $status
 */
class InternshipApplication extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name', 'email', 'phone', 'track', 'message', 'resume_path', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => InternshipApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
