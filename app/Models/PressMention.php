<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PressMention extends Model
{
    use HasFactory;

    protected $fillable = ['outlet_name', 'logo_path', 'url', 'published_on', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
