<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The row-locked sequence table backing DocumentNumberGenerator. Never
 * queried directly outside that service.
 */
class DocumentNumberSequence extends Model
{
    protected $fillable = ['type', 'year', 'last_number'];
}
