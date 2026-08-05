<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Certificates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')->required()->maxLength(190),
                TextInput::make('issuing_authority')->maxLength(190),
                DatePicker::make('valid_from'),
                DatePicker::make('valid_until'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            Textarea::make('description')->rows(3),
            // Content-sniffed server-side via Filament's mimetypes: rule, not
            // extension-matched — matches the ceiling NoticeForm's attachment
            // field already established for the same document/image mix.
            FileUpload::make('file_path')
                ->label('Certificate file')
                ->required()
                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                ->maxSize(5120)
                ->disk('public')
                ->directory('certificates')
                ->helperText('PDF or image, max 5 MB.'),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
