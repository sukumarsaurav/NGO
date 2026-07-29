<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Designations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DesignationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true),
                TextInput::make('rank')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower ranks sort first on the org chart.'),
                // letter_template_id becomes a real Select once document_templates
                // exists (M04, Sprint 4) — see the migration's comment. A numeric
                // field pointing at a table that doesn't exist yet is worse than
                // no field at all.
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
