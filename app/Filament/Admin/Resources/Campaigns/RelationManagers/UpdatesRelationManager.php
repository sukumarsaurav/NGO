<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\RelationManagers;

use App\Actions\Campaigns\PostCampaignUpdate;
use App\Models\Campaign;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * "Live impact updates" nested under a campaign. `notify_donors` is stored
 * here but the actual email send is Sprint 10's job — see
 * `App\Actions\Campaigns\PostCampaignUpdate`'s docblock.
 */
class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(190),
            Textarea::make('body')->required()->rows(6),
            FileUpload::make('image_path')->image()->maxSize(2048)->disk('public')->directory('campaign-updates'),
            DateTimePicker::make('published_at')->default(now()),
            Toggle::make('notify_donors')->label('Email every donor to this campaign once')->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                IconColumn::make('notify_donors')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        /** @var Campaign $campaign */
                        $campaign = $this->getOwnerRecord();

                        return app(PostCampaignUpdate::class)->handle($campaign, $data, auth()->id());
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
