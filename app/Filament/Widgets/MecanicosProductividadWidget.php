<?php

namespace App\Filament\Widgets;

use App\Models\Mecanico;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MecanicosProductividadWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Productividad y Carga de Trabajo por Mecánico';
    }

    public function table(Table $table): Table
    {
        $fechaInicio = $this->filters['fecha_inicio'] ?? Carbon::now()->startOfMonth();
        $fechaFin = $this->filters['fecha_fin'] ?? Carbon::now()->endOfMonth();

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        return $table
            ->query(
                Mecanico::query()
                    ->where('taller_id', auth()->user()->taller_id)
                    ->where('activo', true)
                    ->withCount([
                        'ordenesServicio as total_servicios' => function (Builder $query) use ($inicio, $fin) {
                            $query->whereBetween('fecha_ingreso', [$inicio, $fin]);
                        },
                        'ordenesServicio as en_proceso_count' => function (Builder $query) use ($inicio, $fin) {
                            $query->whereBetween('fecha_ingreso', [$inicio, $fin])
                                ->whereIn('estatus', ['Ingresado', 'En Revisión', 'Cotizando', 'En Reparación']);
                        },
                        'ordenesServicio as liberados_count' => function (Builder $query) use ($inicio, $fin) {
                            $query->whereBetween('fecha_ingreso', [$inicio, $fin])
                                ->whereIn('estatus', ['Revisión Final', 'Listo', 'Entregado']);
                        }
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Mecánico')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('en_proceso_count')
                    ->label('En Proceso (Taller)')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('liberados_count')
                    ->label('Liberados / Terminados')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_servicios')
                    ->label('Total Asignados')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->defaultSort('total_servicios', 'desc')
            ->actions([
                // --- NUEVO BOTÓN PARA VER EL DESGLOSE DE ÓRDENES ---
                Tables\Actions\Action::make('ver_ordenes')
                    ->label('Ver Trabajos')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->modalHeading(fn (Mecanico $record) => 'Servicios asignados a ' . $record->nombre)
                    ->modalSubmitAction(false) // Ocultamos el botón de guardar porque solo es de lectura
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function (Mecanico $record) use ($inicio, $fin) {
                        // 1. Buscamos las órdenes específicas de este mecánico en las fechas del Dashboard
                        $ordenes = $record->ordenesServicio()
                            ->whereBetween('fecha_ingreso', [$inicio, $fin])
                            ->with('vehiculo')
                            ->orderBy('fecha_ingreso', 'desc')
                            ->get();

                        if ($ordenes->isEmpty()) {
                            return new HtmlString('<p style="text-align:center; color:#6b7280; padding: 20px 0;">No hay órdenes asignadas en este periodo.</p>');
                        }

                        // 2. Construimos una interfaz de tarjetas HTML inyectada directamente
                        $html = '<div style="display: flex; flex-direction: column; gap: 12px; margin-top: 5px;">';

                        foreach ($ordenes as $orden) {
                            // Generamos la ruta directa para editar la orden
                            $url = \App\Filament\Resources\OrdenServicioResource::getUrl('edit', ['record' => $orden->id]);
                            $auto = $orden->vehiculo ? "{$orden->vehiculo->marca} {$orden->vehiculo->modelo}" : 'Auto N/A';

                            // Recortamos la observación a 90 caracteres para que sea un resumen limpio
                            $trabajo = $orden->trabajo_a_realizar ? Str::limit($orden->trabajo_a_realizar, 90) : 'Sin descripción de trabajo...';

                            // Lógica de colores según el estatus
                            $badgeColor = match($orden->estatus) {
                                'Listo', 'Entregado' => '#16a34a',
                                'Revisión Final' => '#2563eb',
                                'En Reparación' => '#dc2626',
                                'Cotizando' => '#0891b2',
                                'En Revisión' => '#d97706',
                                default => '#4b5563',
                            };

                            // Tarjeta interactiva (Efecto Hover incluido)
                            $html .= '
                            <a href="'.$url.'" style="display: block; padding: 16px; border: 1px solid #e5e7eb; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.2s ease-in-out; background-color: #ffffff; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" onmouseover="this.style.borderColor=\'#3b82f6\'; this.style.boxShadow=\'0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)\'" onmouseout="this.style.borderColor=\'#e5e7eb\'; this.style.boxShadow=\'0 1px 2px 0 rgba(0, 0, 0, 0.05)\'">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 700; color: #111827; font-size: 1rem;">Folio: '.$orden->folio.'</span>
                                    <span style="background-color: '.$badgeColor.'15; color: '.$badgeColor.'; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.025em; text-transform: uppercase;">'.$orden->estatus.'</span>
                                </div>
                                <div style="font-size: 0.875rem; color: #374151; font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6b7280;"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                                    '.$auto.'
                                </div>
                                <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.5; display: flex; align-items: flex-start; gap: 6px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6b7280; flex-shrink: 0; margin-top: 2px;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                    <span>'.$trabajo.'</span>
                                </div>
                            </a>';
                        }
                        $html .= '</div>';

                        return new HtmlString($html);
                    })
            ]);
    }
}
