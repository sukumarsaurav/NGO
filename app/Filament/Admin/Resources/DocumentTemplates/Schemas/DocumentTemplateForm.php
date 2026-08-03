<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\DocumentTemplates\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * The `body_html`/`css` textareas are live — every keystroke (on blur)
 * re-renders the preview iframe with sample data via
 * resources/views/filament/admin/document-template-preview.blade.php.
 * See docs/03-ROADMAP.md's Sprint 4 spec: "DocumentTemplateResource with
 * live preview."
 */
class DocumentTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextInput::make('name')->required()->maxLength(120),
                        Select::make('type')
                            ->options(DocumentType::class)
                            ->required()
                            ->live(),
                        Select::make('page_size')
                            ->options(['A4' => 'A4', 'CR80' => 'CR80 (ID card)'])
                            ->default('A4')
                            ->required()
                            ->live(),
                        Select::make('orientation')
                            ->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])
                            ->default('portrait')
                            ->required(),
                        Toggle::make('qr_enabled')->default(true),
                        Toggle::make('is_default'),
                        Toggle::make('is_active')->default(true),
                    ]),

                Textarea::make('body_html')
                    ->label('Body HTML (Blade syntax)')
                    ->required()
                    ->rows(12)
                    ->live(onBlur: true)
                    ->helperText('Use {{ $member->name }}, {{ $member->member_code }}, {{ $document->document_number }}, etc.'),

                Textarea::make('css')
                    ->rows(8)
                    ->live(onBlur: true),

                View::make('filament.admin.document-template-preview')
                    ->viewData(fn (Get $get): array => [
                        'bodyHtml' => $get('body_html'),
                        'css' => $get('css'),
                        'pageSize' => $get('page_size'),
                    ]),
            ]);
    }
}
