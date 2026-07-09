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

class CompraResource extends Resource
{
    protected static ?string $model = Compra::class;

    // ESTO ACOMODA EL MENÚ
    protected static ?string $navigationLabel = 'Compras / Entradas';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?int $navigationSort = 2;

    // MOTOR MATEMÁTICO DE COMPRAS
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
                        \Filament\Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha de Compra')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),
                        \Filament\Forms\Components\TextInput::make('folio')
                            ->placeholder('Autogenerado')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])->columns(4),

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
                                    ->columnSpan(2),

                                \Filament\Forms\Components\TextInput::make('cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $set('subtotal', number_format(floatval($state) * floatval($get('precio_unitario') ?? 0), 2, '.', ''));
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Costo Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $set('subtotal', number_format(floatval($state) * floatval($get('cantidad') ?? 1), 2, '.', ''));
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\TextInput::make('subtotal')
                                    ->label('Importe')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
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
                \Filament\Tables\Columns\TextColumn::make('folio')->searchable()->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('proveedor')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                \Filament\Tables\Columns\IconColumn::make('aplica_iva')->label('Facturado')->boolean(),
                \Filament\Tables\Columns\TextColumn::make('total')->money('MXN')->weight('bold')->color('danger'),
            ])
            ->actions([\Filament\Tables\Actions\ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
        ];
    }
}
