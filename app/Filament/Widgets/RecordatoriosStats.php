<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // <-- TRAIT CRUCIAL PARA FILTROS
use App\Models\Recordatorio;
use Carbon\Carbon;

class RecordatoriosStats extends BaseWidget
{
    // Conectamos el widget al filtro del Dashboard
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tallerId = auth()->user()->taller_id;

        // Extraemos las fechas del filtro, o usamos el mes actual por defecto si está vacío
        $fechaInicio = $this->filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $this->filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');

        // 1. OPORTUNIDADES DEL PERIODO (Dinero potencial sobre la mesa)
        $oportunidades = Recordatorio::where('taller_id', $tallerId)
            ->whereBetween('fecha_programada', [$fechaInicio, $fechaFin])
            ->count();

        // 2. CARGA ASEGURADA (Citas amarradas para este periodo)
        $citasAgendadas = Recordatorio::where('taller_id', $tallerId)
            ->where('estatus', 'Cita Agendada')
            ->whereBetween('fecha_programada', [$fechaInicio, $fechaFin])
            ->count();

        // 3. TASA DE RETENCIÓN (Éxito midiendo Completados vs Cancelados)
        $completados = Recordatorio::where('taller_id', $tallerId)
            ->where('estatus', 'Completado')
            ->whereBetween('fecha_programada', [$fechaInicio, $fechaFin])
            ->count();

        $cancelados = Recordatorio::where('taller_id', $tallerId)
            ->where('estatus', 'Cancelado')
            ->whereBetween('fecha_programada', [$fechaInicio, $fechaFin])
            ->count();

        // Evitamos división por cero al calcular el porcentaje
        $totalCerrados = $completados + $cancelados;
        $tasaExito = $totalCerrados > 0
            ? round(($completados / $totalCerrados) * 100) . '%'
            : '0%';

        return [
            Stat::make('Oportunidades (Periodo)', $oportunidades)
                ->description('Vehículos programados a regresar')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Carga Asegurada', $citasAgendadas)
                ->description('Citas agendadas confirmadas')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->chart([2, 5, 4, 7, 6, 9, 12]),

            Stat::make('Tasa de Éxito', $tasaExito)
                ->description("{$completados} Completados / {$cancelados} Cancelados")
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($completados >= $cancelados ? 'success' : 'danger'),
        ];
    }
}
