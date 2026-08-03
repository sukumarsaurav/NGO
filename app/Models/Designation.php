<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'rank', 'letter_template_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function letterTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'letter_template_id');
    }
}
