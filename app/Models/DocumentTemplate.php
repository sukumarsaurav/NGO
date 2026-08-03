<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-editable Blade/HTML template for one of the three document types.
 * See docs/02-DATABASE-SCHEMA.md §4.
 *
 * @property DocumentType $type
 * @property array<string, mixed>|null $qr_position
 */
class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'body_html', 'css', 'page_size', 'orientation',
        'background_path', 'qr_enabled', 'qr_position', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'qr_enabled' => 'boolean',
            'qr_position' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<IssuedDocument, $this>
     */
    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class, 'template_id');
    }
}
