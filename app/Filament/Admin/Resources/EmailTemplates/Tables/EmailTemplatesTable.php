<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EmailTemplates\Tables;

use App\Mail\EmailTemplatePreviewMail;
use App\Models\EmailTemplate;
use App\Services\EmailTemplates\EmailTemplateRenderer;
use App\Services\Settings\SettingsRepository;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->copyable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('subject')->limit(50),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (EmailTemplate $record) => "Preview — {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(function (EmailTemplate $record) {
                        $rendered = self::sampleRender($record);

                        return [
                            Placeholder::make('subject')->label('Subject')->content($rendered['subject']),
                            Placeholder::make('body')->label('Body')->content(new HtmlString($rendered['body'])),
                        ];
                    }),

                Action::make('testSend')
                    ->label('Test send')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->schema([
                        TextInput::make('to')->email()->required()->default(fn () => Auth::user()?->email),
                    ])
                    ->action(function (EmailTemplate $record, array $data): void {
                        $rendered = self::sampleRender($record);
                        $orgName = app(SettingsRepository::class)->get('org.name');

                        Mail::to($data['to'])->send(new EmailTemplatePreviewMail($rendered['subject'], $rendered['body'], $orgName));

                        Notification::make()->title("Test email sent to {$data['to']}")->success()->send();
                    }),
            ])
            ->defaultSort('key');
    }

    /**
     * @return array{subject: string, body: string}
     */
    private static function sampleRender(EmailTemplate $record): array
    {
        $sample = collect($record->available_variables)
            ->mapWithKeys(fn (string $variable) => [$variable => '['.str($variable)->headline().']'])
            ->all();

        return app(EmailTemplateRenderer::class)->render($record->key, $sample);
    }
}
