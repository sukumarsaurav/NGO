<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CsrInquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CsrInquiryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('organisation_name'),
                TextEntry::make('contact_name'),
                TextEntry::make('email'),
                TextEntry::make('phone'),
                TextEntry::make('status')->badge(),
            ]),
            TextEntry::make('message')->columnSpanFull(),
            Grid::make(2)->schema([
                TextEntry::make('reviewedBy.name')->label('Reviewed by')->placeholder('—'),
                TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
            ]),
            TextEntry::make('review_notes')->placeholder('—')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
