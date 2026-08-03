<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Schemas;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

/**
 * See docs/modules/M08-campaigns-crowdfunding.md's admin section and
 * docs/07-SEO.md §5 for the SEO tab requirements (SERP + WhatsApp preview,
 * slug lock once published, alt text required on the cover).
 */
class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Campaign')->tabs([
                Tab::make('Details')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->required()->maxLength(190)->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get, $set) {
                                if (blank($get('slug'))) {
                                    $set('slug', str($state)->slug());
                                }
                            }),
                        TextInput::make('subtitle')->maxLength(255),
                        Select::make('category_id')
                            ->label('Cause category')
                            ->options(fn () => CampaignCategory::query()->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('beneficiary_name')->maxLength(150)
                            ->helperText('Shown as "by {beneficiary}" on cards.'),
                        TextInput::make('goal_amount')
                            ->label('Goal amount (₹)')
                            ->numeric()->required()->minValue(1)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('video_url')->url()->maxLength(255),
                    ]),
                ]),

                Tab::make('Story')->schema([
                    RichEditor::make('story')->required()
                        ->helperText('Aim for 300+ words — thin stories rank poorly and convert worse.')
                        ->columnSpanFull(),
                ]),

                Tab::make('Media')->schema([
                    FileUpload::make('cover_image_path')
                        ->label('Cover image')
                        ->image()->maxSize(2048)
                        ->disk('public')
                        ->directory('campaigns')
                        ->imageEditor()
                        ->live(),
                    TextInput::make('cover_image_alt')
                        ->label('Cover image alt text')
                        ->maxLength(255)
                        ->requiredWith('cover_image_path')
                        ->helperText('Describes the image for screen readers and search engines — required whenever a cover image is set.'),
                ]),

                Tab::make('Flags & schedule')->schema([
                    Grid::make(2)->schema([
                        Toggle::make('allows_recurring')->label('Allows monthly giving')->default(true),
                        Toggle::make('is_tax_benefit')->label('80G tax benefit')->default(true),
                        Toggle::make('is_featured')->label('Featured (homepage carousel)'),
                        Toggle::make('is_urgent')->label('Urgent'),
                        DateTimePicker::make('starts_at'),
                        DateTimePicker::make('ends_at'),
                        TextInput::make('offline_raised_amount')
                            ->label('Offline raised (₹) — cheques, bank transfers')
                            ->numeric()->default(0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),
                ]),

                Tab::make('Impact & FAQs')->schema([
                    Repeater::make('stats')
                        ->relationship()
                        ->label('Campaign impact stats')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('label')->required()->maxLength(100)->placeholder('Dogs Rescued'),
                                TextInput::make('value')->required()->maxLength(20)->placeholder('5,000'),
                                TextInput::make('suffix')->maxLength(5)->placeholder('+'),
                            ]),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(0)
                        ->addActionLabel('Add stat')
                        ->helperText('Per-campaign counters — "5,000+ Dogs Rescued". Distinct from the site-wide homepage stats.'),

                    Repeater::make('faqs')
                        ->relationship()
                        ->label('Campaign-specific FAQs')
                        ->schema([
                            TextInput::make('question')->required()->maxLength(255),
                            Textarea::make('answer')->required()->rows(2),
                            Toggle::make('is_published')->default(true),
                        ])
                        ->orderColumn('sort_order')
                        ->defaultItems(0)
                        ->addActionLabel('Add FAQ')
                        ->helperText('Shown in addition to the six global FAQs every campaign already carries.'),
                ]),

                Tab::make('SEO')->schema([
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(190)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Campaign $record) => $record !== null && $record->status->value !== 'draft')
                        ->dehydrated()
                        ->helperText(fn (?Campaign $record) => $record !== null && $record->status->value !== 'draft'
                            ? 'Locked — this campaign has been published. Changing it elsewhere (e.g. via the API) creates a 301 redirect from the old URL automatically.'
                            : 'Free to edit until this campaign is published.'),
                    TextInput::make('meta_title')->maxLength(190)
                        ->helperText('Falls back to "{title} — Donate Now | {org name}" if left blank.'),
                    Textarea::make('meta_description')->rows(2)->maxLength(255)
                        ->helperText('Falls back to the first 155 characters of the story if left blank.'),
                ]),
            ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }
}
