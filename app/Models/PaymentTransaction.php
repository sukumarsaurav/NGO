<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * What the gateway did — one row per attempt. A donation with three failed
 * card attempts and one successful UPI attempt is one `donations` row and
 * four `payment_transactions` rows. See docs/02-DATABASE-SCHEMA.md §5.
 *
 * @property TransactionStatus $status
 * @property Carbon|null $captured_at
 * @property Carbon|null $refunded_at
 */
class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_id', 'provider', 'provider_order_id', 'provider_payment_id',
        'provider_signature', 'amount', 'fee', 'tax', 'net_amount', 'status',
        'method', 'bank', 'vpa', 'card_last4', 'error_code', 'error_description',
        'raw_response', 'captured_at', 'refunded_at', 'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'amount' => 'integer',
            'fee' => 'integer',
            'tax' => 'integer',
            'net_amount' => 'integer',
            'raw_response' => 'array',
            'captured_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
