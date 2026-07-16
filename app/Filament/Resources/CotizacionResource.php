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

    protected static ?string $navigationGroup = 'Operación del Taller';
    protected static ?string $modelLabel = 'Cotización';
    protected static ?string $pluralModelLabel = 'Cotizaciones';
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?int $navigationSort = 2;



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
                                // 1. BUSCADOR DE CATÁLOGO (Auto-llena los datos y guarda el ID)
                                \Filament\Forms\Components\Select::make('articulo_id')
                                    ->label('Buscar Catálogo')
                                    // Filtramos para que solo muestre Productos que controlan stock
                                    ->options(fn () => \App\Models\Articulo::where('taller_id', auth()->user()->taller_id)
                                        ->where('tipo', 'Producto')
                                        ->where('maneja_stock', true)
                                        ->pluck('nombre', 'id'))
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
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.folio')
                    ->label('Orden de Servicio')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('folio')->searchable()->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.vehiculo.placas')->label('Vehículo')->searchable(),
                \Filament\Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'secondary' => 'Borrador',
                        'primary' => 'Enviada',
                        'success' => 'Aprobada',
                        'danger' => 'Rechazada',
                    ]),
                // Indicador visual premium si ya está pagado
                \Filament\Tables\Columns\IconColumn::make('pagado')
                    ->label('Pago')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                \Filament\Tables\Columns\IconColumn::make('factura_solicitada')
                    ->label('Factura')
                    ->getStateUsing(function (\App\Models\Cotizacion $record) {
                        // Busca el pago asociado a esta cotización en la tabla de transacciones
                        $transaccion = \App\Models\Transaccion::where('cotizacion_id', $record->id)->first();
                        return $transaccion ? (bool) $transaccion->requiere_factura : false;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text') // Muestra un documento si pidió factura
                    ->falseIcon('heroicon-o-minus')        // Muestra una rayita si no pidió
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Indica si el cliente solicitó factura al realizar el pago'),
                // -------------------------------------------

                \Filament\Tables\Columns\TextColumn::make('total')->money('mxn')->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Fecha')->sortable()->dateTime('d/m/Y'),
            ])

            ->actions([
                \Filament\Tables\Actions\EditAction::make(),

                // --- NUEVO: BOTÓN DE CAMBIO RÁPIDO DE ESTATUS ---
                \Filament\Tables\Actions\Action::make('cambiar_estatus')
                    ->label('Estatus')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Actualizar Estatus de la Cotización')
                    ->modalWidth('sm')
                    ->form([
                        \Filament\Forms\Components\Select::make('estatus')
                            ->hiddenLabel()
                            ->options([
                                'Borrador' => 'Borrador',
                                'Enviada' => 'Enviada al Cliente',
                                'Aprobada' => 'Aprobada',
                                'Rechazada' => 'Rechazada',
                            ])
                            ->default(fn (\App\Models\Cotizacion $record) => $record->estatus)
                            ->required(),
                    ])
                    ->action(function (\App\Models\Cotizacion $record, array $data): void {
                        $record->update(['estatus' => $data['estatus']]);

                        \Filament\Notifications\Notification::make()
                            ->title('Estatus actualizado a: ' . $data['estatus'])
                            ->success()
                            ->send();
                    }),
                // --- FIN DEL NUEVO BOTÓN ---

                // EL BOTÓN DE COBRO (MODAL ACTUALIZADO CON REFERENCIA)
                \Filament\Tables\Actions\Action::make('cobrar')
                    ->label('Cobrar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
//                    ->hidden(fn (\App\Models\Cotizacion $record) => $record->pagado)
                    ->modalHeading(fn (\App\Models\Cotizacion $record) => 'Cobrar Folio: ' . $record->folio)
                    ->modalDescription('Confirme el método de pago y registre el folio de rastreo si aplica.')
                    ->modalSubmitActionLabel('Registrar Ingreso')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('monto_a_cobrar')
                            ->label('Monto Total a Cobrar')
                            ->default(fn (\App\Models\Cotizacion $record) => $record->total)
                            ->disabled()
                            ->numeric()
                            ->prefix('$'),

                        \Filament\Forms\Components\Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->options([
                                'Efectivo' => 'Efectivo',
                                'Tarjeta de Débito' => 'Tarjeta de Débito',
                                'Tarjeta de Crédito' => 'Tarjeta de Crédito',
                                'Transferencia SPEI' => 'Transferencia SPEI',
                            ])
                            ->required()
                            ->live(), // Hace que el formulario escuche el cambio al instante

                        // CAMPO DINÁMICO DE REFERENCIA
                        \Filament\Forms\Components\TextInput::make('referencia')
                            ->label('Número de Referencia / Autorización')
                            ->placeholder('Ej. 0928374')
                            // Solo es obligatorio y visible si NO es efectivo
                            ->required(fn (\Filament\Forms\Get $get) => in_array($get('metodo_pago'), ['Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia SPEI']))
                            ->visible(fn (\Filament\Forms\Get $get) => in_array($get('metodo_pago'), ['Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia SPEI'])),

                        \Filament\Forms\Components\Toggle::make('requiere_factura')
                            ->label('El cliente solicita Factura (CFDI)')
                            ->inline(false)
                            ->onColor('success'),
                    ])
                    ->action(function (\App\Models\Cotizacion $record, array $data) {
                        // 1. Registramos el ingreso con todo y referencia
                        \App\Models\Transaccion::create([
                            'taller_id' => $record->taller_id,
                            'cotizacion_id' => $record->id,
                            'tipo' => 'Ingreso',
                            // --- ESTA ES LA LÍNEA QUE CAMBIAMOS ---
                            'concepto' => "Pago de cotización: {$record->folio} orden de servicio: {$record->ordenServicio->folio}",
                            // --------------------------------------
                            'monto' => $record->total,
                            'metodo_pago' => $data['metodo_pago'],
                            'referencia' => $data['referencia'] ?? null, // Guardamos el rastreo
                            'requiere_factura' => $data['requiere_factura'],
                            'fecha' => now(),
                        ]);

                        // 2. Marcamos la cotización como pagada
                        $record->update([
                            'pagado' => true,
                            'estatus' => 'Aprobada'
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('¡Cobro exitoso!')
                            ->body('El ingreso y la referencia se han guardado correctamente.')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\Action::make('imprimir')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (\App\Models\Cotizacion $record) => route('cotizacion.imprimir', $record))
                    ->openUrlInNewTab(),

                // EL BOTÓN MÁGICO DE WHATSAPP
                \Filament\Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (\App\Models\Cotizacion $record) {
                        $orden = $record->ordenServicio;
                        $vehiculo = $orden->vehiculo;
                        $cliente = $vehiculo->cliente;
                        $taller = $orden->taller;

                        // 1. Limpiamos el teléfono (quitamos espacios o guiones)
                        $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);

                        // 2. Si el teléfono tiene 10 dígitos, le agregamos el +52 automáticamente
                        if (strlen($telefono) == 10) {
                            $telefono = '52' . $telefono;
                        }

                        // 3. Generamos el link público
                        $link = route('portal.status', $orden->token_url);
                        $nombreTaller = $taller ? $taller->nombre_comercial : 'Autonix';

                        // 4. Redactamos el mensaje persuasivo y claro
                        $mensaje = "Hola *{$cliente->nombre}*, somos de *{$nombreTaller}* 👨‍🔧.\n\nTe compartimos el estatus actualizado y el presupuesto de tu *{$vehiculo->marca} {$vehiculo->modelo}*.\n\nPuedes revisarlo directo en este enlace seguro:\n👉 {$link}\n\nQuedamos a tu disposición por cualquier duda.";

                        // 5. Retornamos la URL oficial de la API de WhatsApp
                        return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                    })
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
