<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The row-locked sequence table backing MemberCodeGenerator. Never queried
 * directly outside that service — see app/Services/Numbering/MemberCodeGenerator.php.
 */
class MemberCodeSequence extends Model
{
    protected $fillable = ['year', 'last_number'];
}
