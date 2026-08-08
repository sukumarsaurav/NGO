<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserRole;
use App\Facades\Settings;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A single settings page, not a resource — there's only ever one settings
 * record set, so a resource's list view would be nonsense. See
 * docs/modules/M02-organisation-settings.md.
 *
 * @property-read Schema $form
 */
class OrganisationSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Organisation Settings';

    protected string $view = 'filament.admin.pages.organisation-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_settings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill($this->currentFormData());
    }

    /**
     * Filament treats a dot in a component name as a NESTED state path (the
     * same convention used for `TextInput::make('user.name')` on a
     * relationship) — it does not treat 'org.name' as a literal flat key.
     * Every field below is therefore named with underscores
     * (`self::fname('org.name')` -> `org_name`), and the two conversions
     * below are the only place that mapping exists. Get this wrong and every
     * field in the form silently binds to nothing, passing `required()`
     * validation against a value that was never actually filled.
     */
    private static function fname(string $settingKey): string
    {
        return str_replace('.', '_', $settingKey);
    }

    /**
     * Reverses fname(). Safe because none of our setting groups
     * (org/donation/receipt/social/homepage/seo) contain an underscore
     * themselves, so the first underscore in a field name is always exactly
     * where the original dot was.
     */
    private static function settingKey(string $fieldName): string
    {
        return Str::replaceFirst('_', '.', $fieldName);
    }

    /**
     * @return array<string, mixed>
     */
    private function currentFormData(): array
    {
        $all = Settings::all();

        // Money settings are stored in paise; the form works in rupees.
        foreach (['donation.min_amount', 'donation.require_pan_above', 'donation.cash_80g_limit'] as $moneyKey) {
            if (array_key_exists($moneyKey, $all) && $all[$moneyKey] !== null) {
                $all[$moneyKey] = Money::fromPaise((int) $all[$moneyKey])->toRupees();
            }
        }

        if (! empty($all['donation.preset_amounts'])) {
            $all['donation.preset_amounts'] = array_map(
                fn (int $paise) => Money::fromPaise($paise)->toRupees(),
                $all['donation.preset_amounts']
            );
        }

        $all['homepage.steps'] ??= [];
        $all['homepage.videos'] ??= [];

        // PAN is never hydrated into the editable field — see panField().
        // Left out entirely so a blank submit can't accidentally wipe it.
        unset($all['org.pan']);

        return collect($all)
            ->mapWithKeys(fn ($value, string $key) => [self::fname($key) => $value])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        $this->organisationTab(),
                        $this->donationTab(),
                        $this->receiptTab(),
                        $this->socialTab(),
                        $this->homepageTab(),
                        $this->seoTab(),
                    ]),
            ]);
    }

    private function organisationTab(): Tab
    {
        return Tab::make('Organisation')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->schema([
                $this->eightyGeeBanner(),

                Section::make('Identity')
                    ->schema([
                        TextInput::make(self::fname('org.name'))->label('Display name')->required()->maxLength(190),
                        TextInput::make(self::fname('org.legal_name'))->label('Full registered name')->maxLength(190),
                        TextInput::make(self::fname('org.tagline'))->maxLength(190),
                        TextInput::make(self::fname('org.member_code_prefix'))->label('Member code prefix')->required()->maxLength(20)
                            ->helperText('e.g. VGWGF — produces VGWGF-2026-00123.'),
                        FileUpload::make(self::fname('org.logo'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                        FileUpload::make(self::fname('org.logo_dark'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                        FileUpload::make(self::fname('org.favicon'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextInput::make(self::fname('org.address_line1'))->maxLength(190),
                        TextInput::make(self::fname('org.address_line2'))->maxLength(190),
                        TextInput::make(self::fname('org.city'))->maxLength(80),
                        TextInput::make(self::fname('org.state'))->maxLength(80),
                        TextInput::make(self::fname('org.pincode'))->maxLength(10),
                    ])
                    ->columns(2),

                Section::make('Contact')
                    ->schema([
                        TextInput::make(self::fname('org.email'))->email()->maxLength(190),
                        TextInput::make(self::fname('org.phone'))->tel()->maxLength(20),
                        TextInput::make(self::fname('org.whatsapp'))->tel()->maxLength(20)
                            ->helperText('Powers the floating WhatsApp button — hidden site-wide when empty.'),
                    ])
                    ->columns(3),

                Section::make('Registration & compliance')
                    ->description('PAN and validity dates appear on every 80G receipt. Get these right before Sprint 6.')
                    ->schema([
                        TextInput::make(self::fname('org.registration_number'))->label('Society / Trust / Sec 8 registration')->maxLength(190),
                        TextInput::make(self::fname('org.registration_date'))->maxLength(190),
                        TextInput::make(self::fname('org.12a_number'))->label('12A number')->maxLength(190),
                        TextInput::make(self::fname('org.csr_number'))->label('CSR-1 number')->maxLength(190),
                        TextInput::make(self::fname('org.80g_number'))->label('80G registration number')->maxLength(190),
                        TextInput::make(self::fname('org.80g_valid_from'))->label('80G valid from'),
                        // The expiry banner above reflects the SAVED value, not
                        // this field's live edits — it recomputes on the next
                        // render after save() flushes the settings cache.
                        TextInput::make(self::fname('org.80g_valid_to'))->label('80G valid to')->helperText($this->eightyGeeHelperText(...)),
                        $this->panField(),
                    ])
                    ->columns(2),

                Section::make('Authorised signatory')
                    ->schema([
                        TextInput::make(self::fname('org.authorised_signatory_name'))->maxLength(190),
                        TextInput::make(self::fname('org.authorised_signatory_designation'))->maxLength(190),
                        FileUpload::make(self::fname('org.signature_image'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                        FileUpload::make(self::fname('org.seal_image'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * PAN is masked, never hydrated into the field, and only overwritten when
     * the admin actually types a new value — see currentFormData(). Reveal is
     * gated to super-admin and shows the real value via a notification rather
     * than toggling the field in place, which avoids the classic Filament
     * pitfall of an unmodified masked placeholder being submitted as the new
     * "real" value.
     */
    private function panField(): TextInput
    {
        return TextInput::make(self::fname('org.pan'))
            ->label('NGO PAN')
            ->maxLength(10)
            ->placeholder(fn () => Settings::get('org.pan') ? Str::mask(Settings::get('org.pan'), 'X', 0, 5) : 'Not set')
            ->helperText('Leave blank to keep the current PAN unchanged.')
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->suffixAction(
                Action::make('revealPan')
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn () => auth()->user()?->hasRole(UserRole::SuperAdmin->value) && Settings::get('org.pan'))
                    ->action(function () {
                        Notification::make()
                            ->title('Current PAN')
                            ->body(Settings::get('org.pan'))
                            ->persistent()
                            ->send();
                    })
            );
    }

    private function eightyGeeBanner(): Callout
    {
        return Callout::make(fn () => match ($this->eightyGeeStatus()) {
            'expired' => "The NGO's 80G registration has expired.",
            'expiring' => "The NGO's 80G registration expires soon.",
            default => '',
        })
            ->color(fn () => $this->eightyGeeStatus() === 'expired' ? 'danger' : 'warning')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->description(fn () => $this->eightyGeeHelperText())
            ->visible(fn () => $this->eightyGeeStatus() !== 'ok');
    }

    private function eightyGeeHelperText(): ?string
    {
        return match ($this->eightyGeeStatus()) {
            'expired' => '80G receipt issuance is blocked until this is renewed (see docs/modules/M07-receipts-80g.md).',
            'expiring' => 'Renew before it lapses — 80G issuance stops the day this passes.',
            default => null,
        };
    }

    /** @return 'ok'|'expiring'|'expired' */
    private function eightyGeeStatus(): string
    {
        $validTo = Settings::get('org.80g_valid_to');

        if (! $validTo) {
            return 'ok';
        }

        try {
            $date = Carbon::parse($validTo);
        } catch (\Throwable) {
            return 'ok';
        }

        if ($date->isPast()) {
            return 'expired';
        }

        // 60-day warning window, per docs/modules/M02-organisation-settings.md.
        return $date->lessThanOrEqualTo(now()->addDays(60)) ? 'expiring' : 'ok';
    }

    private function donationTab(): Tab
    {
        return Tab::make('Donation')
            ->icon(Heroicon::OutlinedHeart)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make(self::fname('donation.min_amount'))->label('Minimum donation (₹)')->numeric()->required()->minValue(1),
                        TextInput::make(self::fname('donation.currency'))->maxLength(3)->required(),
                        Toggle::make(self::fname('donation.allow_anonymous'))->label('Allow anonymous donations'),
                        TextInput::make(self::fname('donation.require_pan_above'))->label('Require PAN above (₹)')->numeric()->required(),
                        TextInput::make(self::fname('donation.cash_80g_limit'))->label('Cash 80G ineligibility limit (₹)')->numeric()->required()
                            ->helperText('Section 80G: cash donations above this amount are not tax-deductible.'),
                        TagsInput::make(self::fname('donation.preset_amounts'))->label('Preset amounts (₹)')
                            ->placeholder('Add an amount and press enter')
                            ->helperText('Shown as quick-select chips on the donation form.'),
                    ])
                    ->columns(2),
            ]);
    }

    private function receiptTab(): Tab
    {
        return Tab::make('Receipt')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make(self::fname('receipt.prefix'))->label('Donation receipt prefix')->required(),
                        TextInput::make(self::fname('receipt.80g_prefix'))->label('80G receipt prefix')->required(),
                        TextInput::make(self::fname('receipt.number_padding'))->numeric()->required()->minValue(1)->maxValue(10),
                        Toggle::make(self::fname('receipt.auto_email'))->label('Email receipts automatically on issue'),
                        Textarea::make(self::fname('receipt.footer_note'))->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private function socialTab(): Tab
    {
        return Tab::make('Social')
            ->icon(Heroicon::OutlinedShare)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make(self::fname('social.facebook'))->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        TextInput::make(self::fname('social.instagram'))->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        TextInput::make(self::fname('social.twitter'))->label('X (Twitter)')->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        TextInput::make(self::fname('social.linkedin'))->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        TextInput::make(self::fname('social.youtube'))->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                    ])
                    ->columns(2),
            ]);
    }

    private function homepageTab(): Tab
    {
        return Tab::make('Homepage')
            ->icon(Heroicon::OutlinedHome)
            ->schema([
                Section::make('Who we serve')
                    ->schema([
                        TextInput::make(self::fname('homepage.serve_heading'))->maxLength(190),
                        Textarea::make(self::fname('homepage.serve_body')),
                        FileUpload::make(self::fname('homepage.serve_image'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...)),
                    ]),

                Section::make('Monthly giving promo')
                    ->schema([
                        TextInput::make(self::fname('homepage.monthly_heading'))->maxLength(190),
                        Textarea::make(self::fname('homepage.monthly_body')),
                    ]),

                Section::make('How to donate — 4 steps')
                    ->schema([
                        Repeater::make(self::fname('homepage.steps'))
                            ->schema([
                                TextInput::make('title')->required()->maxLength(80),
                                Textarea::make('body')->required()->rows(2),
                                TextInput::make('icon')->helperText('Heroicon name, e.g. heroicon-o-magnifying-glass'),
                            ])
                            ->columns(3)
                            ->maxItems(4)
                            ->addActionLabel('Add step'),
                    ]),

                Section::make('Featured videos')
                    ->description('Paste ordinary YouTube links — watch, share or embed URLs all work. Leave empty to hide the section.')
                    ->schema([
                        TextInput::make(self::fname('homepage.video_heading'))->maxLength(190),
                        Textarea::make(self::fname('homepage.video_body'))->rows(2),
                        Repeater::make(self::fname('homepage.videos'))
                            ->schema([
                                TextInput::make('url')
                                    ->label('YouTube URL')
                                    ->required()
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('title')
                                    ->label('Caption (optional)')
                                    ->maxLength(120),
                            ])
                            ->columns(2)
                            ->maxItems(6)
                            ->addActionLabel('Add video'),
                    ]),

                Section::make('Newsletter')
                    ->schema([
                        TextInput::make(self::fname('homepage.newsletter_heading'))->maxLength(190),
                    ]),
            ]);
    }

    private function seoTab(): Tab
    {
        return Tab::make('SEO')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make(self::fname('seo.meta_title'))->maxLength(60)
                            ->helperText('Kept under 60 characters — see docs/07-SEO.md §4.'),
                        Textarea::make(self::fname('seo.meta_description'))->maxLength(160),
                        FileUpload::make(self::fname('seo.og_image'))->image()->maxSize(2048)->disk('public')->directory($this->orgDirectory(...))
                            ->helperText('1200×630 — see docs/07-SEO.md.'),
                        TextInput::make(self::fname('seo.google_analytics_id')),
                        TextInput::make(self::fname('seo.google_site_verification')),
                        TextInput::make(self::fname('seo.facebook_pixel_id')),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Storage namespacing per docs/modules/M02-organisation-settings.md — costs
     * nothing now and means a future multi-tenant migration never has to move a file.
     */
    private function orgDirectory(): string
    {
        return 'org/'.Str::slug(Settings::get('org.name', 'organisation'));
    }

    public function save(): void
    {
        // getState() returns underscored field names (org_name) — convert back
        // to real setting keys (org.name) immediately, per fname()/settingKey().
        $state = collect($this->form->getState())
            ->mapWithKeys(fn ($value, string $fieldName) => [self::settingKey($fieldName) => $value])
            ->all();

        foreach (['donation.min_amount', 'donation.require_pan_above', 'donation.cash_80g_limit'] as $moneyKey) {
            if (array_key_exists($moneyKey, $state) && $state[$moneyKey] !== null) {
                $state[$moneyKey] = Money::fromRupees((string) $state[$moneyKey])->toPaise();
            }
        }

        if (array_key_exists('donation.preset_amounts', $state)) {
            $state['donation.preset_amounts'] = array_map(
                fn ($rupees) => Money::fromRupees((string) $rupees)->toPaise(),
                $state['donation.preset_amounts'] ?? []
            );
        }

        $repository = app(SettingsRepository::class);

        foreach ($state as $key => $value) {
            $repository->set($key, $value);
        }

        $this->form->fill($this->currentFormData());

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
