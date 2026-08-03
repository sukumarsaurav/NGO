<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReceiptSeries;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * See docs/02-DATABASE-SCHEMA.md's `receipts` table. `snapshot_data` freezes
 * donor name/address/PAN and org 80G details at issue time — the same reason
 * IssuedDocument freezes member data (M04): a donor's later address change
 * must never rewrite a receipt already sent.
 *
 * @property ReceiptSeries $series
 * @property Carbon|null $emailed_at
 * @property array<string, mixed> $snapshot_data
 */
class Receipt extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'receipt_number', 'sequence_number', 'series', 'revision',
        'donation_id', 'donor_id', 'financial_year', 'amount', 'amount_in_words',
        'snapshot_data', 'file_path', 'issued_on', 'emailed_at', 'email_status',
        'download_count', 'is_cancelled', 'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'series' => ReceiptSeries::class,
            'amount' => 'integer',
            'snapshot_data' => 'array',
            'issued_on' => 'date',
            'emailed_at' => 'datetime',
            'is_cancelled' => 'boolean',
            'revision' => 'integer',
            'sequence_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * @return BelongsTo<Donor, $this>
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}
