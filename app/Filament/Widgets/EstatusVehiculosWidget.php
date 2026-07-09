<?php

namespace App\Filament\Widgets;

use App\Models\OrdenServicio;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstatusVehiculosWidget extends BaseWidget
{
    // Esto hace que el widget abarque todo el ancho de la pantalla
    protected int | string | array $columnSpan = 'full';

    // Lo ponemos de primer lugar en el dashboard
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tallerId = auth()->user()->taller_id;

        // Contamos cuántos autos hay en cada fase principal
        $porRevisar = OrdenServicio::where('taller_id', $tallerId)
            ->whereIn('estatus', ['Ingresado', 'En Revisión', 'Cotizando'])->count();

        $enReparacion = OrdenServicio::where('taller_id', $tallerId)
            ->whereIn('estatus', ['En Reparación', 'Revisión Final'])->count();

        $listos = OrdenServicio::where('taller_id', $tallerId)
            ->where('estatus', 'Listo')->count();

        return [
            Stat::make('Por Revisar / Cotizar', $porRevisar)
                ->description('Vehículos en espera de diagnóstico o aprobación')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('En Rampa (Reparación)', $enReparacion)
                ->description('Vehículos siendo trabajados actualmente')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger'),

            Stat::make('Listos para Entrega', $listos)
                ->description('Esperando a que el cliente lo recoja')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
