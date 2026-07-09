<?php

namespace App\Filament\Widgets;

use App\Models\Transaccion;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // <--- LA MAGIA PARA ESCUCHAR AL DASHBOARD
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters; // <--- HABILITAMOS LA CONEXIÓN

    protected static ?int $sort = 1;

    // Quitamos $filter y getFilters() porque ya no las necesitamos

    protected function getStats(): array
    {
        if (!auth()->check() || !auth()->user()->taller_id) {
            return [];
        }

        $tallerId = auth()->user()->taller_id;

        // 1. OBTENEMOS LAS FECHAS DEL DASHBOARD MAESTRO
        $fechaInicio = $this->filters['fecha_inicio'] ?? Carbon::now()->startOfMonth();
        $fechaFin = $this->filters['fecha_fin'] ?? Carbon::now()->endOfMonth();

        // 2. ASEGURAMOS QUE ABARQUE TODO EL DÍA
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        // 3. ETIQUETA VISUAL (Ej. "01/06/2026 - 30/06/2026")
        $etiqueta = $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y');

        // 4. CALCULAMOS EL DINERO EXACTO EN ESAS FECHAS
        $ingresos = Transaccion::where('taller_id', $tallerId)
            ->where('tipo', 'Ingreso')
            ->whereBetween('fecha', [$inicio, $fin])
            ->sum('monto');

        $egresos = Transaccion::where('taller_id', $tallerId)
            ->where('tipo', 'Egreso')
            ->whereBetween('fecha', [$inicio, $fin])
            ->sum('monto');

        $utilidad = $ingresos - $egresos;

        return [
            Stat::make('Ingresos Totales', '$' . number_format($ingresos, 2))
                ->description($etiqueta) // Muestra las fechas debajo del monto
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Egresos Totales', '$' . number_format($egresos, 2))
                ->description($etiqueta)
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Utilidad Neta', '$' . number_format($utilidad, 2))
                ->description($etiqueta)
                ->descriptionIcon($utilidad >= 0 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($utilidad >= 0 ? 'primary' : 'warning'),
        ];
    }
}
