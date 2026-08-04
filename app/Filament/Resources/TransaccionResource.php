<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaccionResource\Pages;
use App\Models\Transaccion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage; // <-- Importante para guardar archivos
use Illuminate\Support\Facades\Http;

class TransaccionResource extends Resource
{
    protected static ?string $model = Transaccion::class;

    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Caja y Finanzas';
    protected static ?string $modelLabel = 'Movimiento de Caja';
    protected static ?string $pluralModelLabel = 'Caja y Finanzas';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                \Filament\Forms\Components\Section::make('Detalles del Movimiento')
                    ->schema([
                        \Filament\Forms\Components\Select::make('tipo')
                            ->options([
                                'Ingreso' => 'Ingreso (Entrada de dinero)',
                                'Egreso' => 'Egreso (Gasto / Salida de dinero)',
                            ])
                            ->required()
                            ->live(),

                        \Filament\Forms\Components\TextInput::make('concepto')
                            ->label('Concepto / Descripción')
                            ->placeholder('Ej. Pago de luz, Compra de aceite...')
                            ->required()
                            ->columnSpan(2),

                        \Filament\Forms\Components\TextInput::make('monto')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        \Filament\Forms\Components\DatePicker::make('fecha')
                            ->default(now())
                            ->required(),

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
                            ->label('Referencia / Autorización')
                            ->visible(fn (\Filament\Forms\Get $get) => in_array($get('metodo_pago'), ['Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia SPEI'])),

                        \Filament\Forms\Components\Toggle::make('requiere_factura')
                            ->label('¿Genera Factura?')
                            ->inline(false)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                \Filament\Tables\Columns\BadgeColumn::make('tipo')
                    ->colors([
                        'success' => 'Ingreso',
                        'danger' => 'Egreso',
                    ])
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('concepto')
                    ->searchable()
                    ->wrap(),

                \Filament\Tables\Columns\TextColumn::make('monto')
                    ->money('MXN')
                    ->weight('bold')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->searchable()
                    ->toggleable()
                    ->visibleFrom('md'), // Colapsa en móviles

                \Filament\Tables\Columns\IconColumn::make('requiere_factura')
                    ->label('Factura')
                    ->boolean()
                    ->toggleable()
                    ->visibleFrom('md'), // Colapsa en móviles

                \Filament\Tables\Columns\BadgeColumn::make('estado_factura')
                    ->label('Estatus Fiscal')
                    ->colors([
                        'gray' => 'No Facturado',
                        'success' => 'Timbrada',
                        'danger' => 'Cancelada',
                    ])
                    ->toggleable()
                    ->visibleFrom('md'), // Colapsa en móviles
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'Ingreso' => 'Solo Ingresos',
                        'Egreso' => 'Solo Egresos',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('estado_factura')
                    ->options([
                        'No Facturado' => 'Pendientes de Timbrar',
                        'Timbrada' => 'Facturadas',
                    ]),
            ])
            ->headerActions([
                // --- RECUPERAMOS EL MODAL DE CORTE DE CAJA ---
                \Filament\Tables\Actions\Action::make('exportar_corte')
                    ->label('Corte de Caja (Exportar)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->modalHeading('Generar Corte de Caja')
                    ->modalDescription('Selecciona el periodo para calcular y exportar los ingresos y egresos.')
                    ->modalSubmitActionLabel('Descargar Reporte')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\DatePicker::make('desde')
                                ->label('Desde la fecha:')
                                ->default(now()->startOfMonth()) // Sugiere el primer día del mes actual
                                ->required(),

                            \Filament\Forms\Components\DatePicker::make('hasta')
                                ->label('Hasta la fecha:')
                                ->default(now()->endOfMonth()) // Sugiere el último día del mes actual
                                ->required(),
                        ]),

                        \Filament\Forms\Components\Select::make('formato')
                            ->label('Formato de descarga')
                            ->options([
                                'pdf' => 'Documento PDF (.pdf)',
                                'excel' => 'Hoja de Cálculo (.xlsx)'
                            ])
                            ->default('pdf')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Aquí pasamos las fechas y el formato a tu controlador de exportación
                        // NOTA: Asegúrate de que el nombre de la ruta ('transacciones.exportar')
                        // coincida con el nombre que tienes declarado en tu archivo web.php
                        return redirect()->route('transacciones.exportar', [
                            'desde' => $data['desde'],
                            'hasta' => $data['hasta'],
                            'formato' => $data['formato'],
                        ]);
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([

                    \Filament\Tables\Actions\EditAction::make(),

                    \Filament\Tables\Actions\Action::make('enviar_whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (\App\Models\Transaccion $record) => $record->estado_factura === 'Timbrada' && !empty($record->factura_id))
                        ->url(function (\App\Models\Transaccion $record) {
                            $cliente = $record->cotizacion->ordenServicio->vehiculo->cliente ?? null;
                            $telefono = $cliente ? preg_replace('/[^0-9]/', '', $cliente->telefono) : '';

                            $linkPdf = route('descargar.factura.pdf', $record->id);
                            $linkXml = route('descargar.factura.xml', $record->id);

                            $mensaje = "Hola, te enviamos los enlaces para descargar tu factura de Autonix.\n\n📄 *PDF:* {$linkPdf}\n\n🧾 *XML:* {$linkXml}\n\nGracias por tu preferencia.";

                            return "https://wa.me/52{$telefono}?text=" . urlencode($mensaje);
                        })
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('descargar_pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-text')
                        ->color('danger')
                        ->visible(fn (\App\Models\Transaccion $record) => $record->estado_factura === 'Timbrada' && !empty($record->factura_id))
                        ->url(fn (\App\Models\Transaccion $record) => route('descargar.factura.pdf', $record->id))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('descargar_xml')
                        ->label('XML')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->visible(fn (\App\Models\Transaccion $record) => $record->estado_factura === 'Timbrada' && !empty($record->factura_id))
                        ->url(fn (\App\Models\Transaccion $record) => route('descargar.factura.xml', $record->id))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('timbrar_factura')
                        ->label('Timbrar')
                        ->icon('heroicon-o-bolt')
                        ->color('warning')
                        ->visible(fn (\App\Models\Transaccion $record) => $record->tipo === 'Ingreso' && $record->requiere_factura && $record->estado_factura === 'No Facturado')
                        ->modalHeading('Timbrar CFDI 4.0')
                        ->modalSubmitActionLabel('Sí, Timbrar Factura')
                        ->form([
                            \Filament\Forms\Components\Select::make('uso_cfdi')
                                ->label('Uso de CFDI')
                                ->options([
                                    'G01' => 'G01 - Adquisición de mercancías',
                                    'G02' => 'G02 - Devoluciones, descuentos o bonificaciones',
                                    'G03' => 'G03 - Gastos en general',
                                    'I03' => 'I03 - Equipo de transporte (Flotillas)',
                                    'I04' => 'I04 - Equipo de cómputo y accesorios',
                                    'I08' => 'I08 - Otra maquinaria y equipo',
                                    'S01' => 'S01 - Sin efectos fiscales (Público en General)',
                                ])
                                ->default('G03')
                                ->required()
                                ->searchable(),

                            \Filament\Forms\Components\Select::make('metodo_pago_sat')
                                ->label('Método de Pago (SAT)')
                                ->options([
                                    'PUE' => 'PUE - Pago en una sola exhibición (Ya liquidado)',
                                    'PPD' => 'PPD - Pago en parcialidades o diferido (Fiado / Pago a plazos)',
                                ])
                                ->default('PUE')
                                ->required(),
                        ])
                        ->action(function (\App\Models\Transaccion $record, array $data) {
                            $taller = $record->taller;

                            $apiKey = $taller->facturacion_produccion ? $taller->facturapi_key_live : $taller->facturapi_key_test;
                            $tenantKey = $taller->facturacion_produccion ? $taller->fiscalapi_tenant_live : $taller->fiscalapi_tenant_test;

                            if (empty($apiKey) || empty($tenantKey)) {
                                \Filament\Notifications\Notification::make()->title('Faltan credenciales')->danger()->send();
                                return;
                            }

                            $cotizacion = $record->cotizacion;
                            $cliente = $cotizacion ? ($cotizacion->ordenServicio->vehiculo->cliente ?? null) : null;

                            if (!$cliente || empty($cliente->rfc)) {
                                \Filament\Notifications\Notification::make()->title('Datos incompletos')->body('El cliente no tiene RFC registrado.')->danger()->send();
                                return;
                            }

                            $metodosSAT = ['Efectivo' => '01', 'Transferencia SPEI' => '03', 'Tarjeta de Crédito' => '04', 'Tarjeta de Débito' => '28'];
                            $formaPago = $data['metodo_pago_sat'] === 'PPD' ? '99' : ($metodosSAT[$record->metodo_pago] ?? '99');

                            $rfcReceptor = strtoupper(trim($cliente->rfc));
                            $usoCfdi = $rfcReceptor === 'XAXX010101000' ? 'S01' : $data['uso_cfdi'];

                            // --- LÓGICA FINANCIERA DE PRECISIÓN ---
                            $conceptos = [];
                            $descuentoGlobal = (float) $cotizacion->descuento;
                            $subtotalGlobal = (float) $cotizacion->subtotal;

                            // Obtenemos el porcentaje de descuento global para aplicarlo por partida
                            $ratioDescuento = $subtotalGlobal > 0 ? ($descuentoGlobal / $subtotalGlobal) : 0;

                            foreach ($cotizacion->items as $index => $item) {
                                $claveSat = $item->articulo->clave_sat ?? '81141601';
                                $unidadSat = $item->articulo->unidad_sat ?? 'E48';

                                $precioUnitarioBase = (float) $item->precio_unitario; // Subtotal estrictamente sin IVA
                                $cantidad = (float) $item->cantidad;

                                // Calculamos el descuento proporcional para este concepto
                                $descuentoItem = ($precioUnitarioBase * $cantidad) * $ratioDescuento;

                                // Armamos los impuestos dinámicamente según la cotización
                                $impuestos = [];

                                if ($cotizacion->iva > 0) {
                                    $impuestos[] = [
                                        'taxCode' => '002', // IVA
                                        'taxTypeCode' => 'Tasa',
                                        'taxRate' => '0.160000',
                                        'taxFlagCode' => 'T' // Traslado (Se suma)
                                    ];
                                }

                                if ($cotizacion->retencion_isr > 0) {
                                    $impuestos[] = [
                                        'taxCode' => '001', // ISR
                                        'taxTypeCode' => 'Tasa',
                                        'taxRate' => '0.012500',
                                        'taxFlagCode' => 'R' // Retención (Se resta)
                                    ];
                                }

                                $conceptos[] = [
                                    'itemCode' => $claveSat,
                                    'quantity' => $cantidad,
                                    'unitOfMeasurementCode' => $unidadSat,
                                    'description' => $item->descripcion,
                                    'unitPrice' => $precioUnitarioBase, // Factura toma el subtotal
                                    'discount' => round($descuentoItem, 2), // Aplica el descuento SAT
                                    'taxObjectCode' => count($impuestos) > 0 ? '02' : '01', // 02: Sí objeto de impuesto, 01: No objeto
                                    'itemSku' => 'ART-' . ($item->articulo_id ?? $index + 1),
                                    'itemTaxes' => $impuestos
                                ];
                            }

                            try {
                                $baseUrl = $taller->facturacion_produccion ? 'https://live.fiscalapi.com' : 'https://test.fiscalapi.com';
                                $rfcTaller = strtoupper(trim($taller->rfc));

                                $responseEmisores = \Illuminate\Support\Facades\Http::acceptJson()
                                    ->withHeaders(['X-API-KEY' => $apiKey, 'X-TENANT-KEY' => $tenantKey])
                                    ->get("{$baseUrl}/api/v4/people", ['pageSize' => 50]);

                                $issuerId = null;
                                if ($responseEmisores->successful()) {
                                    foreach ($responseEmisores->json('data.items') ?? [] as $persona) {
                                        if (isset($persona['tin']) && strtoupper(trim($persona['tin'])) === $rfcTaller) {
                                            $issuerId = $persona['id'];
                                            break;
                                        }
                                    }
                                }

                                if (!$issuerId) {
                                    \Filament\Notifications\Notification::make()->title('Emisor no encontrado en Fiscal API')->danger()->send();
                                    return;
                                }

                                $response = \Illuminate\Support\Facades\Http::acceptJson()
                                    ->withHeaders([
                                        'X-API-KEY' => $apiKey,
                                        'X-TENANT-KEY' => $tenantKey,
                                        'X-TIME-ZONE' => 'America/Mexico_City',
                                    ])
                                    ->post("{$baseUrl}/api/v4/invoices", [
                                        'versionCode' => '4.0',
                                        'series' => 'F',
                                        'date' => now()->setTimezone('America/Mexico_City')->format('Y-m-d\TH:i:s'),
                                        'paymentFormCode' => $formaPago,
                                        'currencyCode' => 'MXN',
                                        'typeCode' => 'I',
                                        'expeditionZipCode' => trim($taller->codigo_postal),
                                        'paymentMethodCode' => $data['metodo_pago_sat'],
                                        'exchangeRate' => 1,
                                        'exportCode' => '01',
                                        'issuer' => ['id' => $issuerId],
                                        'recipient' => [
                                            'tin' => $rfcReceptor,
                                            'legalName' => strtoupper(trim($cliente->razon_social)),
                                            'zipCode' => trim($cliente->codigo_postal),
                                            'taxRegimeCode' => substr($cliente->regimen_fiscal, 0, 3),
                                            'cfdiUseCode' => $usoCfdi,
                                            'email' => $cliente->email ?? ''
                                        ],
                                        'items' => $conceptos
                                    ]);

                                if ($response->successful()) {
                                    $factura = $response->json();
                                    $fiscalApiId = $factura['data']['id'];

                                    $record->update([
                                        'estado_factura' => 'Timbrada',
                                        'factura_id' => $fiscalApiId,
                                    ]);

                                    \Filament\Notifications\Notification::make()->title('¡Factura Timbrada!')->success()->send();
                                } else {
                                    $statusCode = $response->status();
                                    $errorData = $response->json();
                                    $mensajeReal = "Error HTTP {$statusCode} - ";

                                    if (is_array($errorData)) {
                                        $mensajeReal .= $errorData['message'] ?? $errorData['error'] ?? 'Validación rechazada';
                                        if (isset($errorData['data']) && is_array($errorData['data'])) {
                                            $detalles = [];
                                            foreach ($errorData['data'] as $falla) {
                                                if (isset($falla['propertyName']) && isset($falla['errorMessage'])) {
                                                    $detalles[] = "[Campo: " . $falla['propertyName'] . "] " . $falla['errorMessage'];
                                                }
                                            }
                                            if (count($detalles) > 0) $mensajeReal .= " | Detalles exactos: " . implode(' | ', $detalles);
                                        } elseif (isset($errorData['details']) && !empty($errorData['details'])) {
                                            $mensajeReal .= ': ' . (is_string($errorData['details']) ? $errorData['details'] : json_encode($errorData['details']));
                                        }
                                    } else {
                                        $mensajeReal .= "Respuesta del servidor: " . substr($response->body(), 0, 200);
                                    }

                                    \Filament\Notifications\Notification::make()->title('El SAT / Fiscal API rechazó la factura')->body($mensajeReal)->danger()->persistent()->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->title('Error de conexión')->body($e->getMessage())->danger()->send();
                            }
                        }),
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
            'index' => Pages\ListTransaccions::route('/'),
            'create' => Pages\CreateTransaccion::route('/create'),
            'edit' => Pages\EditTransaccion::route('/{record}/edit'),
        ];
    }
}
