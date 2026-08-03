<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Schemas;

use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class NoticeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('audience')->badge()->formatStateUsing(fn (NoticeAudience $state) => $state->label()),
                TextEntry::make('priority')->badge()->color(fn (NoticePriority $state) => $state->color())->formatStateUsing(fn (NoticePriority $state) => $state->label()),
                TextEntry::make('status')->badge()->color(fn (NoticeStatus $state) => $state->color())->formatStateUsing(fn (NoticeStatus $state) => $state->label()),
                TextEntry::make('recipient_count')->label('Recipients'),
                TextEntry::make('read_count')->label('Read by'),
                TextEntry::make('published_at')->dateTime()->placeholder('Not sent'),
            ]),
            TextEntry::make('title')->columnSpanFull(),
            TextEntry::make('body')->html()->columnSpanFull(),
        ]);
    }
}
