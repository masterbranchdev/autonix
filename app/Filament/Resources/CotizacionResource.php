<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotizacionResource\Pages;
use App\Models\Cotizacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CotizacionResource extends Resource
{
    protected static ?string $model = Cotizacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Cotizaciones';
    protected static ?string $pluralModelLabel = 'Cotizaciones';
    protected static ?string $modelLabel = 'Cotización';

    // --- EL CEREBRO DE LAS MATEMÁTICAS EN TIEMPO REAL ---
    public static function updateTotals(Get $get, Set $set): void
    {
        $isInsideRepeater = $get('../../items') !== null;

        $items = $isInsideRepeater ? $get('../../items') : $get('items');
        $aplicarIva = $isInsideRepeater ? $get('../../aplicar_iva') : $get('aplicar_iva');
        $descuento = floatval($isInsideRepeater ? $get('../../descuento') : $get('descuento'));

        $subtotal = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $subtotal += floatval($item['cantidad'] ?? 0) * floatval($item['precio_unitario'] ?? 0);
            }
        }

        // Seguro para evitar que den un descuento mayor al costo de las piezas
        if ($descuento > $subtotal) {
            $descuento = $subtotal;
            if ($isInsideRepeater) {
                $set('../../descuento', number_format($descuento, 2, '.', ''));
            } else {
                $set('descuento', number_format($descuento, 2, '.', ''));
            }
        }

        // El IVA se calcula sobre el Subtotal YA CON EL DESCUENTO aplicado
        $subtotalConDescuento = $subtotal - $descuento;
        $iva = $aplicarIva ? $subtotalConDescuento * 0.16 : 0;
        $total = $subtotalConDescuento + $iva;

        // Actualizamos los campos visuales
        if ($isInsideRepeater) {
            $set('../../subtotal', number_format($subtotal, 2, '.', ''));
            $set('../../iva', number_format($iva, 2, '.', ''));
            $set('../../total', number_format($total, 2, '.', ''));
        } else {
            $set('subtotal', number_format($subtotal, 2, '.', ''));
            $set('iva', number_format($iva, 2, '.', ''));
            $set('total', number_format($total, 2, '.', ''));
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                // DATOS PRINCIPALES
                \Filament\Forms\Components\Section::make('Datos de la Cotización')
                    ->schema([
                        \Filament\Forms\Components\Select::make('orden_servicio_id')
                            ->label('Orden de Servicio (Diagnóstico)')
                            ->relationship('ordenServicio', 'folio')
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\OrdenServicio $record) => "Folio: {$record->folio} - " . ($record->vehiculo ? $record->vehiculo->placas : ''))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        \Filament\Forms\Components\Select::make('estatus')
                            ->options([
                                'Borrador' => 'Borrador',
                                'Enviada' => 'Enviada al Cliente',
                                'Aprobada' => 'Aprobada',
                                'Rechazada' => 'Rechazada',
                            ])
                            ->default('Borrador')
                            ->required()
                            ->columnSpan(1),

                        \Filament\Forms\Components\TextInput::make('folio')
                            ->placeholder('Automático')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])->columns(4),

                // REFACCIONES Y MANO DE OBRA
                \Filament\Forms\Components\Section::make('Conceptos (Refacciones y Mano de Obra)')
                    ->schema([

                        // 3. EL BOTÓN MÁGICO PARA CARGAR PAQUETES (Componente independiente arriba del repetidor)
                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('cargar_paquete')
                                ->label('Cargar Paquete Prearmado')
                                ->icon('heroicon-o-archive-box-arrow-down')
                                ->color('success')
                                ->form([
                                    \Filament\Forms\Components\Select::make('paquete_id')
                                        ->label('Seleccionar Paquete')
                                        ->options(fn () => \App\Models\Paquete::where('taller_id', auth()->user()->taller_id)->pluck('nombre', 'id'))
                                        ->required()
                                        ->searchable(),
                                ])
                                ->action(function (array $data, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                    $paquete = \App\Models\Paquete::find($data['paquete_id']);
                                    if (!$paquete || empty($paquete->items)) return;

                                    $itemsActuales = $get('items') ?? [];

                                    foreach ($paquete->items as $itemPaquete) {
                                        $articulo = \App\Models\Articulo::find($itemPaquete['articulo_id']);
                                        if ($articulo) {
                                            // Insertamos las filas del paquete como nuevas filas
                                            $itemsActuales[(string) str()->uuid()] = [
                                                'articulo_id' => $articulo->id,
                                                'descripcion' => $articulo->nombre,
                                                'cantidad' => $itemPaquete['cantidad'],
                                                'precio_unitario' => $itemPaquete['precio_especial'],
                                                'subtotal' => number_format(floatval($itemPaquete['cantidad']) * floatval($itemPaquete['precio_especial']), 2, '.', ''),
                                            ];
                                        }
                                    }

                                    $set('items', $itemsActuales);

                                    // Forzamos la actualización matemática del total general
                                    $subtotal = 0;
                                    foreach ($itemsActuales as $item) {
                                        $subtotal += floatval($item['cantidad'] ?? 0) * floatval($item['precio_unitario'] ?? 0);
                                    }
                                    $iva = $get('aplicar_iva') ? $subtotal * 0.16 : 0;
                                    $set('subtotal', number_format($subtotal, 2, '.', ''));
                                    $set('iva', number_format($iva, 2, '.', ''));
                                    $set('total', number_format($subtotal + $iva, 2, '.', ''));
                                })
                        ]),

                        \Filament\Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                // 1. BUSCADOR DE CATÁLOGO (Auto-llena los datos)
                                \Filament\Forms\Components\Select::make('articulo_id')
                                    ->label('Buscar Catálogo')
                                    ->options(fn () => \App\Models\Articulo::where('taller_id', auth()->user()->taller_id)->pluck('nombre', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $articulo = \App\Models\Articulo::find($state);
                                            if ($articulo) {
                                                $set('descripcion', $articulo->nombre);
                                                $set('precio_unitario', $articulo->precio);
                                                $set('subtotal', number_format(floatval($get('cantidad')) * floatval($articulo->precio), 2, '.', ''));
                                                self::updateTotals($get, $set);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                // 2. LOS CAMPOS NORMALES
                                \Filament\Forms\Components\TextInput::make('descripcion')
                                    ->label('Concepto (Manual)')
                                    ->required()
                                    ->columnSpan(2),

                                \Filament\Forms\Components\TextInput::make('cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('subtotal', number_format(floatval($get('cantidad')) * floatval($get('precio_unitario')), 2, '.', ''));
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('subtotal', number_format(floatval($get('cantidad')) * floatval($get('precio_unitario')), 2, '.', ''));
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpan(1),
                            ])
                            ->columns(7) // Expandimos las columnas
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                            ->addActionLabel('Agregar Fila Manual')
                            ->columnSpanFull(),
                    ]),

                // TOTALES Y NOTAS
                \Filament\Forms\Components\Section::make('Resumen y Totales')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('notas')
                            ->label('Notas o Garantías para el Cliente')
                            ->rows(4)
                            ->columnSpan(2),

                        \Filament\Forms\Components\Grid::make(1)
                            ->schema([
                                // BOTÓN MÁGICO DEL IVA
                                \Filament\Forms\Components\Toggle::make('aplicar_iva')
                                    ->label('Aplicar IVA (16%)')
                                    ->live()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record ? $record->iva > 0 : false)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),

                                // NUEVO CAMPO DE DESCUENTO
                                \Filament\Forms\Components\TextInput::make('descuento')
                                    ->label('Descuento (Moneda)')
                                    ->numeric()
                                    ->prefix('-$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

                                \Filament\Forms\Components\TextInput::make('iva')
                                    ->label('I.V.A.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),

                                \Filament\Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'font-weight: bold; font-size: 1.2rem; color: #16a34a;']),
                            ])
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('folio')->searchable()->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.vehiculo.placas')->label('Vehículo')->searchable(),
                \Filament\Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'secondary' => 'Borrador',
                        'primary' => 'Enviada',
                        'success' => 'Aprobada',
                        'danger' => 'Rechazada',
                    ]),
                \Filament\Tables\Columns\TextColumn::make('total')->money('mxn')->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),

                // Botón de imprimir cotización
                \Filament\Tables\Actions\Action::make('imprimir')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (\App\Models\Cotizacion $record) => route('cotizacion.imprimir', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotizacions::route('/'),
            'create' => Pages\CreateCotizacion::route('/create'),
            'edit' => Pages\EditCotizacion::route('/{record}/edit'),
        ];
    }
}
