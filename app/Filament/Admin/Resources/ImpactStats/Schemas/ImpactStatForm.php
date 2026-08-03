<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ImpactStats\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ImpactStatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(4)->schema([
                TextInput::make('label')->required()->maxLength(100),
                TextInput::make('value')->required()->maxLength(20),
                TextInput::make('suffix')->maxLength(5),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            TextInput::make('icon')->maxLength(60)->helperText('Optional heroicon name.'),
        ]);
    }
}
