<?php

namespace App\Filament\Widgets;

use App\Models\Taller;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AutonixStatsWidget extends BaseWidget
{
    // Esto hace que las tarjetas aparezcan en la parte superior del Dashboard
    protected static ?int $sort = 1;
    protected static bool $isDiscovered = false;

    // EL CANDADO MAESTRO: Solo tú puedes ver estas métricas financieras
    public static function canView(): bool
    {
        return auth()->user()->email === 'admin@autonix.com.mx';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Talleres', Taller::count())
                ->description('Talleres registrados históricamente')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('gray'),

            Stat::make('Talleres Activos (Pago)', Taller::where('activo', true)->where('plan', '!=', 'prueba')->count())
                ->description('Clientes monetizados')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Prospectos en Demo', Taller::where('plan', 'prueba')->count())
                ->description('En periodo de prueba')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                // Una gráfica de línea decorativa pequeña (Sparkline)
                ->chart([7, 2, 10, 3, 15, 4, 8]),
        ];
    }
}
