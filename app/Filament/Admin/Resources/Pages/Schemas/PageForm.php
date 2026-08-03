<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')->tabs([
                Tab::make('Content')->schema([
                    TextInput::make('title')->required()->maxLength(190)->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            if (blank($get('slug'))) {
                                $set('slug', str($state)->slug());
                            }
                        }),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(190)
                        ->unique(ignoreRecord: true)
                        ->rules([
                            fn () => function (string $attribute, $value, $fail) {
                                if (in_array($value, Page::reservedSlugs(), true)) {
                                    $fail("\"{$value}\" is a reserved URL and can't be used as a page slug.");
                                }
                            },
                        ]),
                    RichEditor::make('body')->required()->columnSpanFull(),
                    Toggle::make('is_published')->default(true),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
                Tab::make('SEO')->schema([
                    TextInput::make('meta_title')->maxLength(190)
                        ->helperText('Falls back to "{title} | {org name}" if left blank.'),
                    Textarea::make('meta_description')->rows(2)->maxLength(255)
                        ->helperText('Falls back to the first 155 characters of the page body if left blank.'),
                ]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }
}
