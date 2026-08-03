<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable copy for every automated email — the "Email Action" feature.
 * See docs/02-DATABASE-SCHEMA.md §9 and docs/modules/M09-notices-communication.md.
 *
 * `is_active` is advisory in the admin UI (an editor can mark a template
 * inactive to flag "don't touch this copy yet"); it does not gate whether the
 * underlying transactional Mailable actually sends — those keep firing on
 * their existing triggers regardless, since several of them (receipts,
 * statutory notices) are not optional.
 *
 * @property list<string> $available_variables
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'subject', 'body_html', 'available_variables',
        'is_active', 'send_copy_to_admin',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
            'send_copy_to_admin' => 'boolean',
        ];
    }
}
