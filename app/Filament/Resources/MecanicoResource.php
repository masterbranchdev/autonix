<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MecanicoResource\Pages;
use App\Models\Mecanico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MecanicoResource extends Resource
{
    protected static ?string $model = Mecanico::class;

    protected static ?string $navigationGroup = 'Directorio';
    protected static ?string $modelLabel = 'Mecánico';
    protected static ?string $pluralModelLabel = 'Mecánicos';
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                \Filament\Forms\Components\TextInput::make('nombre')
                    ->label('Nombre Completo')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),

                \Filament\Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono (Opcional)')
                    ->tel()
                    ->maxLength(255)
                    ->columnSpan(1),

                \Filament\Forms\Components\Toggle::make('activo')
                    ->label('Mecánico Activo')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),

                // Cuenta automáticamente en cuántas órdenes está asignado históricamente
                \Filament\Tables\Columns\TextColumn::make('ordenes_servicio_count')
                    ->counts('ordenesServicio')
                    ->label('Servicios Totales')
                    ->badge()
                    ->color('info'),

                \Filament\Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            // --- BOTÓN GLOBAL EN LA CABECERA DE LA TABLA ---
            ->headerActions([
                \Filament\Tables\Actions\Action::make('reporte_general')
                    ->label('Reporte General (Excel)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->modalHeading('Productividad General del Taller')
                    ->modalDescription('Selecciona el periodo para exportar las órdenes de servicio trabajadas por TODOS los mecánicos.')
                    ->modalSubmitActionLabel('Descargar Reporte Global')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\DatePicker::make('desde')
                                ->label('Desde la fecha:')
                                ->default(now()->startOfMonth())
                                ->required(),

                            \Filament\Forms\Components\DatePicker::make('hasta')
                                ->label('Hasta la fecha:')
                                ->default(now()->endOfMonth())
                                ->required(),
                        ]),
                    ])
                    ->action(function (array $data) {
                        $desde = $data['desde'];
                        $hasta = $data['hasta'];
                        $tallerId = auth()->user()->taller_id;

                        // Buscamos a todos los mecánicos del taller con sus órdenes en ese rango de fechas
                        $mecanicos = \App\Models\Mecanico::where('taller_id', $tallerId)
                            ->with(['ordenesServicio' => function ($query) use ($desde, $hasta) {
                                $query->whereBetween('fecha_ingreso', [$desde, $hasta])
                                    ->with('vehiculo.cliente')
                                    ->orderBy('fecha_ingreso', 'asc');
                            }])
                            ->get();

                        // Forzamos la descarga directa del CSV
                        return response()->streamDownload(function () use ($mecanicos) {
                            $file = fopen('php://output', 'w');
                            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM para que Excel lea los acentos

                            // Agregamos la columna "Mecánico" al principio
                            fputcsv($file, ['Mecánico', 'Folio', 'Fecha de Ingreso', 'Estatus', 'Vehículo', 'Cliente', 'Trabajo Asignado']);

                            foreach ($mecanicos as $mecanico) {
                                foreach ($mecanico->ordenesServicio as $o) {
                                    $vehiculo = $o->vehiculo ? "{$o->vehiculo->marca} {$o->vehiculo->modelo}" : 'N/A';
                                    $cliente = ($o->vehiculo && $o->vehiculo->cliente) ? $o->vehiculo->cliente->nombre : 'N/A';
                                    $fecha = \Carbon\Carbon::parse($o->fecha_ingreso)->format('d/m/Y');

                                    fputcsv($file, [
                                        $mecanico->nombre,
                                        $o->folio,
                                        $fecha,
                                        $o->estatus,
                                        $vehiculo,
                                        $cliente,
                                        $o->trabajo_a_realizar ?? 'Sin descripción'
                                    ]);
                                }
                            }
                            fclose($file);
                        }, "Reporte_Productividad_General_{$desde}_al_{$hasta}.csv");
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([
                    \Filament\Tables\Actions\EditAction::make(),

                    // --- NUEVO BOTÓN PARA VER EL DESGLOSE DE ÓRDENES RÁPIDO ---
                    \Filament\Tables\Actions\Action::make('ver_ordenes')
                        ->label('Ver Trabajos')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->modalHeading(fn (Mecanico $record) => 'Últimos servicios asignados a ' . $record->nombre)
                        ->modalSubmitAction(false) // Ocultamos el botón de guardar porque solo es de lectura
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(function (Mecanico $record) {
                            // Buscamos las últimas 20 órdenes de este mecánico específico
                            $ordenes = $record->ordenesServicio()
                                ->with('vehiculo.cliente')
                                ->orderBy('fecha_ingreso', 'desc')
                                ->limit(20)
                                ->get();

                            if ($ordenes->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('<p style="text-align:center; color:#6b7280; padding: 20px 0;">No hay órdenes asignadas a este mecánico.</p>');
                            }

                            // Construimos la interfaz de tarjetas HTML inyectada directamente
                            $html = '<div style="display: flex; flex-direction: column; gap: 12px; margin-top: 5px;">';

                            foreach ($ordenes as $orden) {
                                // Ruta directa para editar la orden al darle clic
                                $url = \App\Filament\Resources\OrdenServicioResource::getUrl('edit', ['record' => $orden->id]);
                                $auto = $orden->vehiculo ? "{$orden->vehiculo->marca} {$orden->vehiculo->modelo}" : 'Auto N/A';

                                // Recortamos la observación a 90 caracteres
                                $trabajo = $orden->trabajo_a_realizar ? \Illuminate\Support\Str::limit($orden->trabajo_a_realizar, 90) : 'Sin descripción de trabajo...';

                                $badgeColor = match($orden->estatus) {
                                    'Listo', 'Entregado' => '#16a34a',
                                    'Revisión Final' => '#2563eb',
                                    'En Reparación' => '#dc2626',
                                    'Cotizando' => '#0891b2',
                                    'En Revisión' => '#d97706',
                                    default => '#4b5563',
                                };

                                // Tarjeta interactiva
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

                            return new \Illuminate\Support\HtmlString($html);
                        }),

                    // --- BOTÓN EXPORTADOR DE PRODUCTIVIDAD (Existente) ---
                    \Filament\Tables\Actions\Action::make('productividad')
                        ->label('Productividad (Excel)')
                        ->icon('heroicon-o-chart-bar')
                        ->color('success')
                        ->modalHeading(fn (Mecanico $record) => 'Productividad: ' . $record->nombre)
                        ->modalDescription('Selecciona el periodo para exportar las órdenes trabajadas por este mecánico.')
                        ->modalSubmitActionLabel('Descargar Reporte')
                        ->form([
                            \Filament\Forms\Components\Grid::make(2)->schema([
                                \Filament\Forms\Components\DatePicker::make('desde')
                                    ->label('Desde la fecha:')
                                    ->default(now()->startOfMonth())
                                    ->required(),

                                \Filament\Forms\Components\DatePicker::make('hasta')
                                    ->label('Hasta la fecha:')
                                    ->default(now()->endOfMonth())
                                    ->required(),
                            ]),
                        ])
                        ->action(function (Mecanico $record, array $data) {
                            $desde = $data['desde'];
                            $hasta = $data['hasta'];

                            // Buscamos las órdenes donde participó en esas fechas
                            $ordenes = $record->ordenesServicio()
                                ->whereBetween('fecha_ingreso', [$desde, $hasta])
                                ->with('vehiculo.cliente') // Traemos datos del auto y dueño
                                ->orderBy('fecha_ingreso', 'asc')
                                ->get();

                            // Forzamos la descarga directa desde el botón
                            return response()->streamDownload(function () use ($ordenes, $record) {
                                $file = fopen('php://output', 'w');
                                fputs($file, "\xEF\xBB\xBF"); // Codificación UTF-8

                                fputcsv($file, ['Folio', 'Fecha de Ingreso', 'Estatus', 'Vehículo', 'Cliente', 'Trabajo Asignado']);

                                foreach($ordenes as $o) {
                                    $vehiculo = $o->vehiculo ? "{$o->vehiculo->marca} {$o->vehiculo->modelo}" : 'N/A';
                                    $cliente = ($o->vehiculo && $o->vehiculo->cliente) ? $o->vehiculo->cliente->nombre : 'N/A';
                                    $fecha = \Carbon\Carbon::parse($o->fecha_ingreso)->format('d/m/Y');

                                    fputcsv($file, [
                                        $o->folio,
                                        $fecha,
                                        $o->estatus,
                                        $vehiculo,
                                        $cliente,
                                        $o->trabajo_a_realizar ?? 'Sin descripción'
                                    ]);
                                }
                                fclose($file);
                            }, "Productividad_" . str_replace(' ', '_', $record->nombre) . "_{$desde}_al_{$hasta}.csv");
                        }),
                ])
                    ->label('Opciones')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary')
                    ->button(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMecanicos::route('/'),
            'create' => Pages\CreateMecanico::route('/create'),
            'edit' => Pages\EditMecanico::route('/{record}/edit'),
        ];
    }
}
