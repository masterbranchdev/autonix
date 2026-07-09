<?php

namespace App\Filament\Widgets;

use App\Models\Transaccion;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // <--- ESCUCHA EL DASHBOARD MAESTRO
use Carbon\Carbon;

class IngresosEgresosChart extends ChartWidget
{
    use InteractsWithPageFilters; // <--- HABILITAMOS LA CONEXIÓN

    protected static ?string $heading = 'Flujo de Efectivo';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    // Quitamos $filter y getFilters() para eliminar por completo el select de la esquinita

    protected function getData(): array
    {
        if (!auth()->check() || !auth()->user()->taller_id) {
            return ['datasets' => [], 'labels' => []];
        }

        $tallerId = auth()->user()->taller_id;

        // 1. OBTENEMOS LAS FECHAS DEL FILTRADOR SUPERIOR
        $fechaInicio = $this->filters['fecha_inicio'] ?? Carbon::now()->startOfMonth();
        $fechaFin = $this->filters['fecha_fin'] ?? Carbon::now()->endOfMonth();

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        // 2. CALCULAMOS LA DIFERENCIA EN DÍAS PARA SABER CÓMO AGRUPAR
        $diferenciaDias = $inicio->diffInDays($fin);

        $etiquetas = [];
        $ingresosData = [];
        $egresosData = [];

        // 3. LÓGICA INTELIGENTE DE BARRAS
        if ($diferenciaDias <= 31) {

            // RANGO CORTO: Mostrar día por día
            $actual = $inicio->copy();
            while ($actual->lte($fin)) {
                $etiquetas[] = $actual->translatedFormat('d M'); // Ej. "22 Jun"

                $ingresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Ingreso')
                    ->whereDate('fecha', $actual->toDateString())
                    ->sum('monto');

                $egresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Egreso')
                    ->whereDate('fecha', $actual->toDateString())
                    ->sum('monto');

                $actual->addDay();
            }

        } elseif ($diferenciaDias <= 365) {

            // RANGO MEDIANO: Mostrar mes por mes
            $actual = $inicio->copy()->startOfMonth();
            while ($actual->lte($fin)) {
                $etiquetas[] = ucfirst($actual->translatedFormat('F Y')); // Ej. "Junio 2026"

                $ingresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Ingreso')
                    ->whereYear('fecha', $actual->year)
                    ->whereMonth('fecha', $actual->month)
                    ->sum('monto');

                $egresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Egreso')
                    ->whereYear('fecha', $actual->year)
                    ->whereMonth('fecha', $actual->month)
                    ->sum('monto');

                $actual->addMonth();
            }

        } else {

            // RANGO LARGO: Mostrar año por año
            $actual = $inicio->copy()->startOfYear();
            while ($actual->lte($fin)) {
                $etiquetas[] = $actual->year; // Ej. "2026"

                $ingresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Ingreso')
                    ->whereYear('fecha', $actual->year)
                    ->sum('monto');

                $egresosData[] = Transaccion::where('taller_id', $tallerId)
                    ->where('tipo', 'Egreso')
                    ->whereYear('fecha', $actual->year)
                    ->sum('monto');

                $actual->addYear();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $ingresosData,
                    'backgroundColor' => '#16a34a',
                    'borderColor' => '#16a34a',
                ],
                [
                    'label' => 'Egresos',
                    'data' => $egresosData,
                    'backgroundColor' => '#dc2626',
                    'borderColor' => '#dc2626',
                ],
            ],
            'labels' => $etiquetas,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
