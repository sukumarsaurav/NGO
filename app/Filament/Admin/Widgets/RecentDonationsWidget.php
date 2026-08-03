<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Donation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentDonationsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent donations')
            ->query(Donation::query()->where('status', 'succeeded')->latest('donated_at')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('donor.name')->label('Donor'),
                TextColumn::make('amount')->money('inr', divideBy: 100),
                TextColumn::make('campaign.title')->label('Campaign')->placeholder('General fund'),
                TextColumn::make('donated_at')->dateTime()->label('Date'),
            ]);
    }
}
