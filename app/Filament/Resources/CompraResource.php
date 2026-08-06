<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompraResource\Pages;
use App\Models\Compra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CompraResource extends Resource
{
    protected static ?string $model = Compra::class;

    protected static ?string $navigationLabel = 'Compras / Entradas';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        // Sin excepciones. Todos los usuarios (incluyéndote) solo ven los datos de su propio taller.
        return parent::getEloquentQuery()->where('taller_id', auth()->user()->taller_id);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $aplicaIva = $get('aplica_iva');

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += floatval($item['cantidad'] ?? 0) * floatval($item['precio_unitario'] ?? 0);
        }

        $iva = $aplicaIva ? $subtotal * 0.16 : 0;
        $total = $subtotal + $iva;

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('iva', number_format($iva, 2, '.', ''));
        $set('total', number_format($total, 2, '.', ''));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                \Filament\Forms\Components\Section::make('Datos del Proveedor y Compra')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('proveedor')
                            ->label('Nombre del Proveedor (Ej. AutoZone, Refaccionaria)')
                            ->required()
                            ->columnSpan(2),

                        \Filament\Forms\Components\TextInput::make('numero_factura')
                            ->label('No. Factura o Ticket')
                            ->placeholder('Ej. F-98765')
                            ->maxLength(50)
                            ->columnSpan(2),

                        \Filament\Forms\Components\TextInput::make('folio')
                            ->placeholder('Autogenerado')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),

                        \Filament\Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha de Compra')
                            ->default(now())
                            ->required()
                            ->columnSpan(2),

                        // --- INICIO NUEVOS CAMPOS DE CRÉDITO ---
                        \Filament\Forms\Components\Select::make('estado_pago')
                            ->label('Condición de Pago')
                            ->options([
                                'Pagado' => 'Pagado (Al contado)',
                                'Crédito' => 'A Crédito (Pendiente)',
                            ])
                            ->default('Pagado')
                            ->live()
                            ->required()
                            ->columnSpan(2),

                        \Filament\Forms\Components\TextInput::make('dias_credito')
                            ->label('Días de Crédito')
                            ->numeric()
                            ->suffix('días')
                            ->placeholder('Ej. 15')
                            ->visible(fn (Get $get) => $get('estado_pago') === 'Crédito')
                            ->required(fn (Get $get) => $get('estado_pago') === 'Crédito')
                            ->columnSpan(2),
                        // --- FIN NUEVOS CAMPOS DE CRÉDITO ---

                    ])->columns(6), // Usamos 6 columnas para que 3 campos de tamaño 2 entren por fila

                \Filament\Forms\Components\Section::make('Artículos Comprados')
                    ->description('Al guardar, estas piezas se sumarán automáticamente a tu inventario.')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('items')
                            ->label('')
                            ->schema([
                                \Filament\Forms\Components\Select::make('articulo_id')
                                    ->label('Refacción / Producto')
                                    ->options(fn () => \App\Models\Articulo::where('taller_id', auth()->user()->taller_id)
                                        ->where('tipo', 'Producto')
                                        ->pluck('nombre', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(['default' => 2, 'md' => 2]), // Toma toda la fila en móvil, 2 en PC

                                \Filament\Forms\Components\TextInput::make('cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                        $set('subtotal', number_format(floatval($state) * floatval($get('precio_unitario') ?? 0), 2, '.', ''));
                                    })
                                    ->columnSpan(1), // Toma 1 columna (mitad de la pantalla en móvil)

                                \Filament\Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Costo Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                        $set('subtotal', number_format(floatval($state) * floatval($get('cantidad') ?? 1), 2, '.', ''));
                                    })
                                    ->columnSpan(1), // Toma 1 columna (mitad de la pantalla en móvil)

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->label('Importe')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpan(['default' => 2, 'md' => 1]), // Toma toda la fila en móvil, 1 en PC
                            ])
                            ->columns(['default' => 2, 'md' => 5]) // Grid responsivo: 2 columnas en móvil, 5 en PC
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (\Filament\Forms\Get $get, \Filament\Forms\Set $set) => self::updateTotals($get, $set))
                            ->columnSpanFull(),
                    ]),

                \Filament\Forms\Components\Section::make('Resumen y Totales')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('notas')
                            ->label('Notas Adicionales (Opcional)')
                            ->rows(4)
                            ->columnSpan(2),

                        \Filament\Forms\Components\Grid::make(1)
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('aplica_iva')
                                    ->label('La factura del proveedor incluye IVA (16%)')
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),

                                \Filament\Forms\Components\TextInput::make('iva')
                                    ->label('I.V.A.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),

                                \Filament\Forms\Components\TextInput::make('total')
                                    ->label('Total Pagado')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraInputAttributes(['style' => 'font-weight: bold; font-size: 1.2rem; color: #dc2626;']),
                            ])
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('folio')
                    ->searchable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('proveedor')
                    ->searchable()
                    ->toggleable()
                    ->visibleFrom('md'),

                \Filament\Tables\Columns\TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                // INDICADOR DE PAGO
                \Filament\Tables\Columns\BadgeColumn::make('estado_pago')
                    ->label('Pago')
                    ->colors([
                        'success' => 'Pagado',
                        'warning' => 'Crédito',
                    ]),

                // CÁLCULO DE VENCIMIENTO
                \Filament\Tables\Columns\TextColumn::make('vencimiento')
                    ->label('Vencimiento')
                    ->getStateUsing(function (Compra $record) {
                        if ($record->estado_pago === 'Crédito' && $record->dias_credito) {
                            return Carbon::parse($record->fecha)->addDays($record->dias_credito)->format('d/m/Y');
                        }
                        return '-';
                    })
                    ->color(function (Compra $record) {
                        if ($record->estado_pago === 'Crédito' && $record->dias_credito) {
                            $vencimiento = Carbon::parse($record->fecha)->addDays($record->dias_credito);
                            return $vencimiento->isPast() ? 'danger' : 'warning';
                        }
                        return 'gray';
                    })
                    ->weight(fn (Compra $record) => $record->estado_pago === 'Crédito' ? 'bold' : 'normal')
                    ->toggleable()
                    ->visibleFrom('md'),

                \Filament\Tables\Columns\TextColumn::make('total')
                    ->money('MXN')
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([

                    \Filament\Tables\Actions\EditAction::make(),
                    \Filament\Tables\Actions\ViewAction::make(),

                    // ACCIÓN RÁPIDA PARA MARCAR COMO PAGADO
                    \Filament\Tables\Actions\Action::make('marcar_pagado')
                        ->label('Marcar Pagado')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Compra $record) => $record->estado_pago === 'Crédito')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Pago a Proveedor')
                        ->modalDescription('¿Confirmas que esta compra a crédito ya fue liquidada al proveedor?')
                        ->modalSubmitActionLabel('Sí, marcar como pagado')
                        ->action(function (Compra $record) {
                            $record->update(['estado_pago' => 'Pagado']);
                            \Filament\Notifications\Notification::make()
                                ->title('Compra marcada como pagada')
                                ->success()
                                ->send();
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
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
        ];
    }
}
