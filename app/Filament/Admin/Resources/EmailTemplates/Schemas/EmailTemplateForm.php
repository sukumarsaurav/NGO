<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * `body_html` is edited as raw HTML, not through a RichEditor — a WYSIWYG
 * editor reformats markup on save and can mangle `{{ variable }}` tokens.
 * See docs/modules/M09-notices-communication.md: substitution is a literal
 * text whitelist, so the source bytes must survive editing untouched.
 */
class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('key')->disabled()->dehydrated(false),
                TextInput::make('name')->required()->maxLength(120),
            ]),
            TextInput::make('subject')->required()->maxLength(190)->columnSpanFull(),
            Textarea::make('body_html')
                ->label('Body (HTML)')
                ->required()
                ->rows(14)
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 13px;'])
                ->columnSpanFull(),
            Placeholder::make('available_variables_display')
                ->label('Available variables')
                ->content(function ($record) {
                    if (! $record) {
                        return new HtmlString('—');
                    }

                    $tokens = collect($record->available_variables)
                        ->map(fn (string $variable) => "<code>{{ {$variable} }}</code>")
                        ->implode(' ');

                    return new HtmlString($tokens ?: '—');
                })
                ->columnSpanFull(),
            Grid::make(2)->schema([
                Toggle::make('is_active'),
                Toggle::make('send_copy_to_admin'),
            ]),
        ]);
    }
}
