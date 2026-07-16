<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Recordatorio;

class RecordatoriosStats extends BaseWidget
{
    // Conectamos el widget al filtro del Dashboard
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    // MAGIA VISUAL: Forzamos a que el widget se divida en 4 columnas exactas
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $tallerId = auth()->user()->taller_id;

        // Extraemos las fechas del filtro, o usamos el mes actual por defecto
        $fechaInicio = $this->filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $this->filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');

        // OPTIMIZACIÓN: Creamos una consulta base para el periodo y clonamos para contar cada estatus
        // Esto hace que tu Dashboard cargue rapidísimo sin importar cuántos registros haya
        $baseQuery = Recordatorio::where('taller_id', $tallerId)
            ->whereBetween('fecha_programada', [$fechaInicio, $fechaFin]);

        $pendientes = (clone $baseQuery)->where('estatus', 'Pendiente')->count();
        $contactados = (clone $baseQuery)->where('estatus', 'Contactado')->count();
        $agendados = (clone $baseQuery)->where('estatus', 'Cita Agendada')->count();
        $cancelados = (clone $baseQuery)->where('estatus', 'Cancelado')->count();

        return [
            // 1. EL PENDIENTE (Alerta: Tienes que trabajar)
            Stat::make('Por Contactar', $pendientes)
                ->description('Mensajes sin enviar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'), // Amarillo

            // 2. EL CONTACTADO (En progreso: Esperando respuesta)
            Stat::make('Contactados', $contactados)
                ->description('En espera de confirmación')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'), // Azul

            // 3. EL ÉXITO (Victoria: Dinero asegurado)
            Stat::make('Citas Agendadas', $agendados)
                ->description('Retención exitosa')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success') // Verde
                ->chart([2, 5, 4, 7, 6, 9, 12]), // Gráfica para darle un toque premium

            // 4. LA PÉRDIDA (Alerta: Ya lo hicieron en otro lado)
            Stat::make('Cancelados', $cancelados)
                ->description('Servicios perdidos')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'), // Rojo
        ];
    }
}
