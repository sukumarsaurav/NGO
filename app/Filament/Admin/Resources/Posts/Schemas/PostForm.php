<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Schemas;

use App\Enums\PostCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Post')->tabs([
                Tab::make('Content')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->required()->maxLength(190)->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, $set) {
                                if (blank($get('slug'))) {
                                    $set('slug', str($state)->slug());
                                }
                            }),
                        TextInput::make('slug')->required()->maxLength(190)->unique(ignoreRecord: true),
                        Select::make('category')->options(PostCategory::class)->required(),
                        Select::make('author_user_id')
                            ->label('Author')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->default(fn () => auth()->id()),
                        DateTimePicker::make('published_at')->default(now()),
                        Toggle::make('is_published')->default(false),
                    ]),
                    Textarea::make('excerpt')->rows(2)->maxLength(500)->columnSpanFull(),
                    FileUpload::make('cover_image_path')->label('Cover image')->image()->maxSize(2048)->disk('public')->directory('posts')->columnSpanFull(),
                    RichEditor::make('body')->required()->columnSpanFull(),
                ]),
                Tab::make('SEO')->schema([
                    TextInput::make('meta_title')->maxLength(190)
                        ->helperText('Falls back to "{title} | {org name} Blog" if left blank.'),
                    Textarea::make('meta_description')->rows(2)->maxLength(255)
                        ->helperText('Falls back to the excerpt if left blank.'),
                ]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }
}
