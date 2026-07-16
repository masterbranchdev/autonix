<?php

namespace App\Filament\Widgets;

use App\Models\Taller;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TalleresChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Crecimiento de Talleres';
    protected static bool $isDiscovered = false;

    // Esto la acomoda justo debajo de las tarjetas
    protected static ?int $sort = 2;

    // EL CANDADO MAESTRO
    public static function canView(): bool
    {
        return auth()->user()->email === 'admin@autonix.com.mx';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Recorremos los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = ucfirst($month->translatedFormat('F')); // Ej. "Julio"

            $data[] = Taller::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nuevos Talleres Registrados',
                    'data' => $data,
                    // Color corporativo personalizado para la gráfica
                    'borderColor' => '#00a3e4',
                    'backgroundColor' => '#00a3e433', // Versión con transparencia para el relleno
                    'fill' => 'start',
                    'tension' => 0.4, // Curvas suaves
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
