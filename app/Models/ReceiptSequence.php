<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Row-locked sequence backing ReceiptNumberGenerator. Never queried directly
 * outside that service.
 */
class ReceiptSequence extends Model
{
    protected $fillable = ['series', 'financial_year', 'prefix', 'last_number', 'locked_at'];

    protected function casts(): array
    {
        return ['locked_at' => 'datetime'];
    }
}
