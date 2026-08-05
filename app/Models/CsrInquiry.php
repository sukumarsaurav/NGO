<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CsrInquiryStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company's "Partner with us" submission from the public CSR Partnership
 * page. Reviewed manually in the admin panel — no automated approval
 * workflow, unlike FundraiserRequest.
 *
 * @property CsrInquiryStatus $status
 */
class CsrInquiry extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'organisation_name', 'contact_name', 'email', 'phone', 'message', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => CsrInquiryStatus::class,
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
