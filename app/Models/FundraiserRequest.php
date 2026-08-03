<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FundraiserRequestStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The "Start a Fundraise" form submission. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 *
 * @property FundraiserRequestStatus $status
 */
class FundraiserRequest extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name', 'email', 'phone', 'organisation_name', 'cause_category_id',
        'title', 'description', 'goal_amount', 'documents', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'campaign_id', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'goal_amount' => 'integer',
            'documents' => 'array',
            'status' => FundraiserRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CampaignCategory, $this>
     */
    public function causeCategory(): BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class, 'cause_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
