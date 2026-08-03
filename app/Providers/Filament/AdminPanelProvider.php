<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Login gating to super-admin + admin lives on User::canAccessPanel() keyed off
 * this panel's id — see docs/modules/M11-manager-panel.md for why this is a
 * second, wholly separate panel rather than a permission-gated /admin.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandLogo(asset('images/branding/logo-horizontal.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon-32x32.png'))
            // Registers the SAME primitives as resources/css/tokens.css.
            // `warning` maps to accent-700, not accent-500 — Filament renders
            // warning badges with white text, and white on accent-500 is the
            // 2.68:1 failure the whole palette was corrected to avoid.
            // See docs/08-DESIGN-SYSTEM.md §12.
            ->colors([
                'primary' => Color::hex('#1f7a4d'),
                'danger' => Color::hex('#c0392b'),
                'warning' => Color::hex('#945b14'),
                'success' => Color::hex('#1f7a4d'),
                'info' => Color::hex('#1f5f7a'),
            ])
            // Off by decision, not omission — docs/08-DESIGN-SYSTEM.md §11.
            // Filament ships this enabled; left alone the admin panel builds
            // itself a second, unrelated appearance from Filament's stock greys.
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
