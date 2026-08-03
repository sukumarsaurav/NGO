<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Receipts\Schemas;

use App\Enums\ReceiptSeries;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('receipt_number'),
                        TextEntry::make('series')->badge()->formatStateUsing(fn (ReceiptSeries $state) => $state->value === '80g' ? '80G' : 'Donation'),
                        TextEntry::make('donor.name')->label('Donor'),
                        TextEntry::make('amount')->money('inr', divideBy: 100),
                        TextEntry::make('financial_year')->label('FY'),
                        TextEntry::make('revision'),
                        TextEntry::make('issued_on')->date(),
                        TextEntry::make('email_status')->badge(),
                        TextEntry::make('is_cancelled')->label('Cancelled?')->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('cancelled_reason')->placeholder('—')->visible(fn ($record) => $record->is_cancelled),
                        TextEntry::make('download_count'),
                    ]),
            ]);
    }
}
