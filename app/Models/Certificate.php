<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'issuing_authority', 'file_path',
        'valid_from', 'valid_until', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
