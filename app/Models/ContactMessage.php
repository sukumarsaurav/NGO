<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'ip_address', 'user_agent', 'is_read', 'read_at'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
