<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CampaignCategories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * See docs/07-SEO.md §1: the eight `/causes/{slug}` pages are "the most
 * durable SEO assets in the project" — `intro_body` needs 150+ words of
 * unique copy, which is why it gets its own tab rather than sitting in a
 * cramped single-page form.
 */
class CampaignCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Category')->tabs([
                Tab::make('Details')->schema([
                    TextInput::make('name')->required()->maxLength(100)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
                    TextInput::make('slug')->required()->maxLength(100)->unique(ignoreRecord: true),
                    FileUpload::make('icon_path')->image()->maxSize(2048)->disk('public')->directory('campaign-categories'),
                    Textarea::make('description')->rows(2)->maxLength(255)
                        ->helperText('Short blurb for the homepage tile.'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_active')->default(true),
                ]),
                Tab::make('Cause page copy')->schema([
                    Textarea::make('intro_body')->rows(10)
                        ->helperText('150+ words of unique copy for the /causes/{slug} page — this is what makes the page rank.'),
                ]),
                Tab::make('SEO')->schema([
                    TextInput::make('meta_title')->maxLength(190)
                        ->helperText('Falls back to "Donate for {Category} — Verified Campaigns | {org name}" if left blank.'),
                    Textarea::make('meta_description')->rows(2)->maxLength(255)
                        ->helperText('Falls back to the first 155 characters of the cause page copy if left blank.'),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
