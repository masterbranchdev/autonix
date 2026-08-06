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
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

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
            ])

            // --- NUEVO: INDICADOR DE MEMBRESÍA EN EL TOPBAR (ESTILO FILAMENT NATIVO) ---
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('
                    @if(auth()->check() && auth()->user()->email !== "admin@autonix.com.mx" && auth()->user()->taller && auth()->user()->taller->vencimiento_suscripcion)
                        @php
                            $fecha = \Carbon\Carbon::parse(auth()->user()->taller->vencimiento_suscripcion)->startOfDay();
                            $hoy = now()->startOfDay();
                            $diasRestantes = (int) round($hoy->diffInDays($fecha, false));

                            // Nombre dinámico del plan desde la DB
                            $nombrePlan = auth()->user()->taller->plan ?? "Estándar";

                            // Estados por defecto (Gris neutral o Verde discreto)
                            $statusColor = "#10b981"; // Verde
                            $statusText = "Suscripción Activa";

                            if ($diasRestantes < 0) {
                                $statusColor = "#dc2626"; // Rojo
                                $statusText = "Suscripción Vencida";
                            } elseif ($diasRestantes === 0) {
                                $statusColor = "#ea580c"; // Naranja Fuerte
                                $statusText = "Vence hoy";
                            } elseif ($diasRestantes <= 5) {
                                $statusColor = "#f59e0b"; // Naranja
                                $statusText = "Vence pronto";
                            }
                        @endphp

                        <!-- Alpine.js Dropdown -->
                        <div x-data="{ open: false }" class="relative hidden sm:block mr-2" style="z-index: 50;">

                            <!-- Botón Trigger Superior -->
                            <button @click="open = !open" @click.away="open = false"
                                style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 6px 12px; display: flex; align-items: center; gap: 8px; background-color: #ffffff; cursor: pointer; transition: background-color 0.2s;"
                                class="hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-700 dark:hover:bg-gray-800">
                                <span style="font-size: 14px; font-weight: 500; color: #374151;" class="dark:text-gray-200">Membresía</span>
                                <svg style="width: 16px; height: 16px; color: #6b7280;" class="transition-transform duration-200" :class="{\'rotate-180\': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <!-- Tarjeta Desplegable (Estilo Filament) -->
                            <div x-show="open" x-transition.opacity
                                style="display: none; min-width: 240px; position: absolute; right: 0; margin-top: 8px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); overflow: hidden;"
                                class="dark:bg-gray-900 dark:border-gray-700">

                                <div style="padding: 12px 16px;">

                                    <!-- Estatus Visual con Icono de Alerta si es necesario -->
                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px;">
                                        @if($diasRestantes <= 5)
                                            <svg style="width: 18px; height: 18px; color: {{ $statusColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @endif
                                        <span style="color: {{ $statusColor }}; font-weight: 600; font-size: 14px;">
                                            {{ $statusText }}
                                        </span>
                                    </div>

                                    <div style="margin-bottom: 12px;">
                                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 600;">Plan Actual</p>
                                        <div style="display: inline-flex; align-items: center; gap: 4px; background-color: #f3f4f6; padding: 4px 10px; border-radius: 9999px; font-size: 13px; font-weight: 500; color: #111827;" class="dark:bg-gray-800 dark:text-gray-200">
                                            🚀 {{ ucfirst($nombrePlan) }}
                                        </div>
                                    </div>

                                    <div>
                                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 600;">Próximo Pago</p>
                                        <div style="display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 500; color: #111827;" class="dark:text-white">
                                            <svg style="width: 16px; height: 16px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ $fecha->format("d/m/Y") }}
                                        </div>
                                    </div>
                                </div>

                                <div style="border-top: 1px solid #e5e7eb; padding: 8px;" class="dark:border-gray-800">
                                    <a href="/perfil-taller" style="display: flex; align-items: center; padding: 8px; border-radius: 6px; font-size: 14px; font-weight: 500; color: #374151; text-decoration: none; transition: background-color 0.2s;" class="hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                        Administrar membresía
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endif
                ')
            ) // <-- AQUÍ CERRAMOS EL PRIMER RENDER HOOK
            ->renderHook( // <-- Y AQUÍ INICIAMOS EL SEGUNDO
                PanelsRenderHook::FOOTER,
                fn () => view('filament.footer')
            );
    }
}
