<?php

namespace App\Filament\Admin\Resources\Donations\Pages;

use App\Actions\Donations\CreateOfflineDonation;
use App\Enums\PaymentMode;
use App\Filament\Admin\Resources\Donations\DonationResource;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordOfflineDonation')
                ->label('Record offline donation')
                ->schema([
                    TextInput::make('donorName')->label('Donor name')->required(),
                    TextInput::make('donorEmail')->label('Donor email')->email()->required(),
                    TextInput::make('donorPhone')->label('Donor phone'),
                    TextInput::make('amount')->label('Amount (₹)')->numeric()->required(),
                    Select::make('paymentMode')
                        ->label('Payment mode')
                        ->options([
                            'cash' => 'Cash',
                            'cheque' => 'Cheque',
                            'bank_transfer' => 'Bank transfer',
                        ])
                        ->required(),
                    Textarea::make('notes'),
                ])
                ->action(function (array $data): void {
                    app(CreateOfflineDonation::class)->handle(
                        donorName: $data['donorName'],
                        donorEmail: $data['donorEmail'],
                        donorPhone: $data['donorPhone'] ?: null,
                        amount: Money::fromRupees((string) $data['amount']),
                        paymentMode: PaymentMode::from($data['paymentMode']),
                        recordedByUserId: (int) Auth::id(),
                        notes: $data['notes'] ?: null,
                    );

                    Notification::make()->title('Offline donation recorded')->success()->send();
                }),
        ];
    }
}
