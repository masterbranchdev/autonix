<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaccionResource\Pages;
use App\Models\Transaccion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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

                // FORMULARIO PARA REGISTROS MANUALES (Ej. Gastos del taller)
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
                    ->searchable(),

                \Filament\Tables\Columns\IconColumn::make('requiere_factura')
                    ->label('Factura')
                    ->boolean(),

                // --- NUEVA COLUMNA DE ESTATUS FISCAL ---
                \Filament\Tables\Columns\BadgeColumn::make('estado_factura')
                    ->label('Estatus Fiscal')
                    ->colors([
                        'gray' => 'No Facturado',
                        'success' => 'Timbrada',
                        'danger' => 'Cancelada',
                    ]),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'Ingreso' => 'Solo Ingresos',
                        'Egreso' => 'Solo Egresos',
                    ]),
                // --- NUEVO FILTRO PARA BUSCAR FACTURAS ---
                \Filament\Tables\Filters\SelectFilter::make('estado_factura')
                    ->options([
                        'No Facturado' => 'Pendientes de Timbrar',
                        'Timbrada' => 'Facturadas',
                    ]),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('corte_caja')
                    ->label('Corte de Caja (Reporte)')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color('primary')
                    ->modalHeading('Generar Corte Financiero')
                    ->modalDescription('Selecciona el periodo que deseas evaluar y el formato de salida.')
                    ->modalSubmitActionLabel('Descargar Reporte')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\DatePicker::make('fecha_inicio')
                                ->label('Fecha de Inicio')
                                ->default(now()->startOfMonth())
                                ->required(),
                            \Filament\Forms\Components\DatePicker::make('fecha_fin')
                                ->label('Fecha de Fin')
                                ->default(now())
                                ->required(),
                        ]),
                        \Filament\Forms\Components\Select::make('formato')
                            ->label('Formato del Reporte')
                            ->options([
                                'pdf' => 'Reporte Ejecutivo (PDF con gráficas)',
                                'excel' => 'Hoja de Cálculo (Excel / CSV)',
                            ])
                            ->default('pdf')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $url = route('finanzas.corte', [
                            'inicio' => $data['fecha_inicio'],
                            'fin' => $data['fecha_fin'],
                            'formato' => $data['formato'],
                        ]);
                        return redirect()->to($url);
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),

                // --- EL BOTÓN MÁGICO DE FACTURAPI ---
                \Filament\Tables\Actions\Action::make('timbrar_factura')
                    ->label('Timbrar')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->button()
                    // Solo es visible si es un Ingreso, requiere factura y aún no se ha timbrado
                    ->visible(fn (Transaccion $record) => $record->tipo === 'Ingreso' && $record->requiere_factura && $record->estado_factura === 'No Facturado')
                    ->requiresConfirmation()
                    ->modalHeading('Timbrar CFDI 4.0')
                    ->modalDescription('¿Estás seguro de generar la factura ante el SAT para esta transacción? Esta acción consumirá timbres de tu cuenta.')
                    ->modalSubmitActionLabel('Sí, Timbrar Factura')
                    ->action(function (Transaccion $record) {
                        // AQUÍ IRA LA LÓGICA DE LA API DE FACTURAPI
                        // Por ahora, simularemos el éxito para ver que la interfaz reacciona

                        $record->update([
                            'estado_factura' => 'Timbrada',
                            'factura_id' => 'simulacion_12345',
                            'url_pdf_factura' => 'https://ejemplo.com/factura.pdf',
                            'url_xml_factura' => 'https://ejemplo.com/factura.xml',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Factura Timbrada con Éxito')
                            ->success()
                            ->send();
                    }),

                // --- BOTONES PARA DESCARGAR PDF Y XML (Aparecen cuando ya se timbró) ---
                \Filament\Tables\Actions\Action::make('descargar_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->visible(fn (Transaccion $record) => $record->estado_factura === 'Timbrada')
                    ->url(fn (Transaccion $record) => $record->url_pdf_factura)
                    ->openUrlInNewTab(),

                \Filament\Tables\Actions\Action::make('descargar_xml')
                    ->label('XML')
                    ->icon('heroicon-o-code-bracket')
                    ->color('gray')
                    ->visible(fn (Transaccion $record) => $record->estado_factura === 'Timbrada')
                    ->url(fn (Transaccion $record) => $record->url_xml_factura)
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
            'index' => Pages\ListTransaccions::route('/'),
            'create' => Pages\CreateTransaccion::route('/create'),
            'edit' => Pages\EditTransaccion::route('/{record}/edit'),
        ];
    }
}
