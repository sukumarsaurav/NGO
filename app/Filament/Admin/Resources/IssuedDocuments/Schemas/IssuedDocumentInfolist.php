<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IssuedDocuments\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class IssuedDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('document_number'),
                        TextEntry::make('type')->badge()->formatStateUsing(fn (DocumentType $state) => $state->label()),
                        TextEntry::make('member.user.name')->label('Member'),
                        TextEntry::make('title'),
                        TextEntry::make('status')->badge()->color(fn (DocumentStatus $state) => $state->color())->formatStateUsing(fn (DocumentStatus $state) => $state->label()),
                        TextEntry::make('issued_on')->date(),
                        TextEntry::make('valid_until')->date()->placeholder('—'),
                        TextEntry::make('revoked_reason')->placeholder('—')->visible(fn ($record) => $record->status === DocumentStatus::Revoked),
                        TextEntry::make('download_count')->label('Downloads'),
                        TextEntry::make('verified_count')->label('QR scans'),
                    ]),
            ]);
    }
}
