<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Every ID card, letter, and certificate ever generated. Immutable once
 * issued — reissuing creates a new row and marks this one `superseded`.
 * See docs/02-DATABASE-SCHEMA.md §4.
 *
 * `snapshot_data` freezes the member's name/department/designation/etc. at
 * issue time — regenerating from live relations would silently rewrite a
 * document that already left the building.
 *
 * @property DocumentType $type
 * @property DocumentStatus $status
 * @property array<string, mixed> $snapshot_data
 * @property Carbon|null $issued_on
 * @property Carbon|null $valid_until
 * @property Carbon|null $revoked_at
 */
class IssuedDocument extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'document_number', 'type', 'member_id', 'template_id', 'title',
        'snapshot_data', 'file_path', 'qr_payload', 'issued_by_user_id',
        'issued_on', 'valid_until', 'status', 'revoked_at', 'revoked_reason',
        'superseded_by_id', 'download_count', 'verified_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'snapshot_data' => 'array',
            'issued_on' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /**
     * @return BelongsTo<IssuedDocument, $this>
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function isRevoked(): bool
    {
        return $this->status === DocumentStatus::Revoked;
    }
}
