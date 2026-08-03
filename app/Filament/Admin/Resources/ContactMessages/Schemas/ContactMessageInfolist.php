<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('subject')->placeholder('—'),
            ]),
            TextEntry::make('message')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }
}
