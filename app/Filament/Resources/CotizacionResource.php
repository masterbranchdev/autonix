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
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class CotizacionResource extends Resource
{
    protected static ?string $model = Cotizacion::class;

    protected static ?string $navigationGroup = 'Operación del Taller';
    protected static ?string $modelLabel = 'Cotización';
    protected static ?string $pluralModelLabel = 'Cotizaciones';
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Sin excepciones. Todos los usuarios (incluyéndote) solo ven los datos de su propio taller.
        return parent::getEloquentQuery()->where('taller_id', auth()->user()->taller_id);
    }

    // --- EL CEREBRO DE LAS MATEMÁTICAS EN TIEMPO REAL ---
    public static function updateTotals(Get $get, Set $set): void
    {
        $isInsideRepeater = $get('../../items') !== null;

        $items = $isInsideRepeater ? $get('../../items') : $get('items');
        $aplicarIva = $isInsideRepeater ? $get('../../aplicar_iva') : $get('aplicar_iva');
        $aplicarIsr = $isInsideRepeater ? $get('../../aplicar_retencion_isr') : $get('aplicar_retencion_isr');
        $descuento = floatval($isInsideRepeater ? $get('../../descuento') : $get('descuento'));

        $subtotal = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $subtotal += floatval($item['cantidad'] ?? 0) * floatval($item['precio_unitario'] ?? 0);
            }
        }

        if ($descuento > $subtotal) {
            $descuento = $subtotal;
            if ($isInsideRepeater) {
                $set('../../descuento', number_format($descuento, 2, '.', ''));
            } else {
                $set('descuento', number_format($descuento, 2, '.', ''));
            }
        }

        $subtotalConDescuento = $subtotal - $descuento;

        $iva = $aplicarIva ? $subtotalConDescuento * 0.16 : 0;
        $retencionIsr = $aplicarIsr ? $subtotalConDescuento * 0.0125 : 0;
        $total = $subtotalConDescuento + $iva - $retencionIsr;

        if ($isInsideRepeater) {
            $set('../../subtotal', number_format($subtotal, 2, '.', ''));
            $set('../../iva', number_format($iva, 2, '.', ''));
            $set('../../retencion_isr', number_format($retencionIsr, 2, '.', ''));
            $set('../../total', number_format($total, 2, '.', ''));
        } else {
            $set('subtotal', number_format($subtotal, 2, '.', ''));
            $set('iva', number_format($iva, 2, '.', ''));
            $set('retencion_isr', number_format($retencionIsr, 2, '.', ''));
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
                            ->label('Orden de Servicio (Opcional)')
                            ->relationship(
                                name: 'ordenServicio',
                                titleAttribute: 'folio',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with('vehiculo.cliente')->latest('id')
                            )
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\OrdenServicio $record) => "Folio: {$record->folio} - " . ($record->vehiculo ? $record->vehiculo->placas : 'Sin placas') . ($record->vehiculo && $record->vehiculo->cliente ? " - {$record->vehiculo->cliente->nombre}" : ''))
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $cleanSearch = \Illuminate\Support\Str::ascii($search);
                                return \App\Models\OrdenServicio::with('vehiculo.cliente')
                                    ->where('folio', 'ilike', "%{$cleanSearch}%")
                                    ->orWhereHas('vehiculo', function ($q) use ($cleanSearch) {
                                        $q->where('placas', 'ilike', "%{$cleanSearch}%")
                                            ->orWhereHas('cliente', fn ($q2) => $q2->where('nombre', 'ilike', "%{$cleanSearch}%"));
                                    })
                                    ->latest('id')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($record) => [
                                        $record->id => "Folio: {$record->folio} - " . ($record->vehiculo ? $record->vehiculo->placas : 'Sin placas') . ($record->vehiculo && $record->vehiculo->cliente ? " - {$record->vehiculo->cliente->nombre}" : '')
                                    ])
                                    ->toArray();
                            })
                            ->preload()
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
                                            $itemsActuales[(string) str()->uuid()] = [
                                                'articulo_id' => $articulo->id,
                                                'descripcion' => $articulo->nombre,
                                                'observaciones' => null, // Inicializamos el campo vacío
                                                'cantidad' => $itemPaquete['cantidad'],
                                                'precio_unitario' => $itemPaquete['precio_especial'],
                                                'subtotal' => number_format(floatval($itemPaquete['cantidad']) * floatval($itemPaquete['precio_especial']), 2, '.', ''),
                                            ];
                                        }
                                    }

                                    $set('items', $itemsActuales);

                                    $subtotal = 0;
                                    foreach ($itemsActuales as $item) {
                                        $subtotal += floatval($item['cantidad'] ?? 0) * floatval($item['precio_unitario'] ?? 0);
                                    }

                                    $iva = $get('aplicar_iva') ? $subtotal * 0.16 : 0;
                                    $retencionIsr = $get('aplicar_retencion_isr') ? $subtotal * 0.0125 : 0;

                                    $set('subtotal', number_format($subtotal, 2, '.', ''));
                                    $set('iva', number_format($iva, 2, '.', ''));
                                    $set('retencion_isr', number_format($retencionIsr, 2, '.', ''));
                                    $set('total', number_format($subtotal + $iva - $retencionIsr, 2, '.', ''));
                                })
                        ]),

                        \Filament\Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                \Filament\Forms\Components\Select::make('articulo_id')
                                    ->label('Buscar Catálogo')
                                    ->options(fn () => \App\Models\Articulo::where('taller_id', auth()->user()->taller_id)
                                        ->pluck('nombre', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
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
                                    ->columnSpan(['default' => 2, 'md' => 2]),

                                \Filament\Forms\Components\TextInput::make('descripcion')
                                    ->label('Concepto (Manual/SAT)')
                                    ->required()
                                    ->columnSpan(['default' => 2, 'md' => 2]),

                                \Filament\Forms\Components\TextInput::make('cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set) {
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
                                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set) {
                                        $set('subtotal', number_format(floatval($get('cantidad')) * floatval($get('precio_unitario')), 2, '.', ''));
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpan(['default' => 2, 'md' => 1]),

                                \Filament\Forms\Components\TextInput::make('observaciones')
                                    ->hiddenLabel()
                                    ->placeholder('Observaciones o detalles de la refacción (Ej. Para Ford Ranger 2015)...')
                                    ->maxLength(255)
                                    ->extraInputAttributes(['style' => 'font-size: 0.85rem; padding-top: 0.35rem; padding-bottom: 0.35rem;'])
                                    ->columnSpanFull(),
                            ])
                            ->columns(['default' => 2, 'md' => 7]) // Responsivo: 2 columnas en celular, 7 en PC
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (\Filament\Forms\Get $get, \Filament\Forms\Set $set) => self::updateTotals($get, $set))
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

                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('aplicar_iva')
                                    ->label('Aplicar IVA')
                                    ->live()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record ? $record->iva > 0 : false)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

                                \Filament\Forms\Components\Toggle::make('aplicar_retencion_isr')
                                    ->label('Retención ISR')
                                    ->live()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record ? $record->retencion_isr > 0 : false)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpanFull(),

                                \Filament\Forms\Components\TextInput::make('descuento')
                                    ->label('Descuento (Moneda)')
                                    ->numeric()
                                    ->prefix('-$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                                    ->columnSpanFull(),

                                \Filament\Forms\Components\TextInput::make('iva')
                                    ->label('I.V.A.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),

                                \Filament\Forms\Components\TextInput::make('retencion_isr')
                                    ->label('Retención ISR')
                                    ->numeric()
                                    ->prefix('-$')
                                    ->readOnly(),

                                \Filament\Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'font-weight: bold; font-size: 1.2rem; color: #16a34a;'])
                                    ->columnSpanFull(),
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
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable(),
                \Filament\Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'secondary' => 'Borrador',
                        'primary' => 'Enviada',
                        'success' => 'Aprobada',
                        'danger' => 'Rechazada',
                    ]),
                \Filament\Tables\Columns\IconColumn::make('pagado')
                    ->label('Pago')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                \Filament\Tables\Columns\TextColumn::make('total')
                    ->money('mxn')
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('folio')
                    ->searchable()
                    ->weight('bold')
                    ->toggleable()
                    ->visibleFrom('md'),
                \Filament\Tables\Columns\IconColumn::make('factura_solicitada')
                    ->label('Factura')
                    ->getStateUsing(function (\App\Models\Cotizacion $record) {
                        $transaccion = \App\Models\Transaccion::where('cotizacion_id', $record->id)->first();
                        return $transaccion ? (bool) $transaccion->requiere_factura : false;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip('Indica si el cliente solicitó factura al realizar el pago')
                    ->toggleable()
                    ->visibleFrom('md'),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->sortable()
                    ->dateTime('d/m/Y')
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([

                    \Filament\Tables\Actions\EditAction::make(),

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

                    \Filament\Tables\Actions\Action::make('cobrar')
                        ->label('Cobrar')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->hidden(fn (\App\Models\Cotizacion $record) => is_null($record->orden_servicio_id))
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
                                ->live(),

                            \Filament\Forms\Components\TextInput::make('referencia')
                                ->label('Número de Referencia / Autorización')
                                ->placeholder('Ej. 0928374')
                                ->required(fn (\Filament\Forms\Get $get) => in_array($get('metodo_pago'), ['Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia SPEI']))
                                ->visible(fn (\Filament\Forms\Get $get) => in_array($get('metodo_pago'), ['Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia SPEI'])),

                            // --- NUEVO: CUADRO DE ADVERTENCIA PARA IMPUESTOS ---
                            \Filament\Forms\Components\Placeholder::make('alerta_impuestos')
                                ->hiddenLabel()
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div style="background-color: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; padding: 12px; border-radius: 4px;">
                                        <strong>⚠️ Facturación</strong><br>
                                        Debido a que esta cotización incluye impuestos (IVA / Retención ISR), se marcará la solicitud de factura en Caja y Finanzas.
                                    </div>
                                '))
                                ->visible(fn (\App\Models\Cotizacion $record) => $record->iva > 0 || $record->retencion_isr > 0)
                                ->columnSpanFull(),
//
//                            // --- TOGGLE TRADICIONAL (Solo visible si NO hay impuestos) ---
//                            \Filament\Forms\Components\Toggle::make('requiere_factura')
//                                ->label('El cliente solicita Factura (CFDI)')
//                                ->inline(false)
//                                ->onColor('success')
//                                ->visible(fn (\App\Models\Cotizacion $record) => $record->iva <= 0 && $record->retencion_isr <= 0)
//                                ->columnSpanFull(),
                        ])
                        ->action(function (\App\Models\Cotizacion $record, array $data) {
                            $transaccionPrevia = \App\Models\Transaccion::where('taller_id', $record->taller_id)
                                ->where('cotizacion_id', $record->id)
                                ->first();

                            if ($transaccionPrevia) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Pago ya registrado')
                                    ->body('Ya existe una transacción para esta cotización. Por favor, borre el ingreso previo en el módulo de Caja y Finanzas antes de intentar cobrar de nuevo.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // 1. Evaluamos de forma 100% segura por backend si requiere factura
                            $debeFacturar = ($record->iva > 0 || $record->retencion_isr > 0) ? true : ($data['requiere_factura'] ?? false);

                            \App\Models\Transaccion::create([
                                'taller_id' => $record->taller_id,
                                'cotizacion_id' => $record->id,
                                'tipo' => 'Ingreso',
                                'concepto' => "Pago de cotización: {$record->folio} orden de servicio: {$record->ordenServicio->folio}",
                                'monto' => $record->total,
                                'metodo_pago' => $data['metodo_pago'],
                                'referencia' => $data['referencia'] ?? null,
                                // 2. Guardamos el valor calculado
                                'requiere_factura' => $debeFacturar,
                                'fecha' => now(),
                            ]);

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

                    \Filament\Tables\Actions\Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->hidden(fn (\App\Models\Cotizacion $record) => is_null($record->orden_servicio_id))
                        ->url(function (\App\Models\Cotizacion $record) {
                            $orden = $record->ordenServicio;
                            $vehiculo = $orden->vehiculo;
                            $cliente = $vehiculo->cliente;
                            $taller = $orden->taller;

                            $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);

                            if (strlen($telefono) == 10) {
                                $telefono = '52' . $telefono;
                            }

                            $link = route('portal.status', $orden->token_url);
                            $nombreTaller = $taller ? $taller->nombre_comercial : 'Autonix';

                            $mensaje = "Hola *{$cliente->nombre}*, somos de *{$nombreTaller}* 👨‍🔧.\n\nTe compartimos el estatus actualizado y el presupuesto de tu *{$vehiculo->marca} {$vehiculo->modelo}*.\n\nPuedes revisarlo directo en este enlace seguro:\n👉 {$link}\n\nQuedamos a tu disposición por cualquier duda.";

                            return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                        })
                        ->openUrlInNewTab(),

                ])
                    ->label('Opciones')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary')
                    ->button(),
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
