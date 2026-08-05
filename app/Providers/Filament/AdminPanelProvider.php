<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\PerfilTaller;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->darkMode(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            // --- INICIO DE TU BRANDING AUTONIX ---
            ->favicon(asset('img/autonix_logo_solo.png'))

            ->brandLogo(fn () => request()->routeIs('filament.*.auth.login')
                ? asset('img/autonix_logo.png')
                : asset('img/autonix_horizontal.png')
            )

            // AUMENTAMOS EL DEL LOGIN (8rem) Y REDUCIMOS EL DEL MENÚ (2rem)
            ->brandLogoHeight(fn () => request()->routeIs('filament.*.auth.login') ? '8rem' : '1.5rem')
            // --- FIN DE TU BRANDING ---
            ->navigationGroups([
                'Directorio',
                'Operación del Taller',
                'Catálogos e Inventario',
                'Administración',
                'Configuraciones',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Perfil del Taller')
                    ->url(fn (): string => PerfilTaller::getUrl())
                    ->icon('heroicon-o-building-storefront')
                    ->visible(function (): bool {
                        $rolesPermitidos = ['admin taller', 'super_admin', 'admin'];

                        return auth()->user()->roles->pluck('name')
                            ->map(fn($rol) => strtolower($rol))
                            ->intersect($rolesPermitidos)
                            ->isNotEmpty();
                    }),
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckSuscripcionTaller::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
            ]);
    }
}
