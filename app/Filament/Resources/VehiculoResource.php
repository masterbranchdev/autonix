<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Filament\Resources\VehiculoResource\RelationManagers;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Campo oculto para el SaaS
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                // Relación directa con el dueño del auto
                \Filament\Forms\Components\Select::make('cliente_id')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),

                \Filament\Forms\Components\TextInput::make('vin')
                    ->maxLength(17),
                \Filament\Forms\Components\TextInput::make('placas')
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('marca')
                    ->datalist([
                        'Nissan', 'Chevrolet', 'Volkswagen', 'Toyota', 'Kia', 'Ford', 'Mazda', 'Honda', 'Hyundai', 'Suzuki', 'Audi', 'BMW', 'Mercedes-Benz', 'Jeep', 'Renault', 'Peugeot', 'Fiat', 'Seat'
                    ])
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('modelo')
                    ->maxLength(255)
                    ->required()
                    ->hint('Ej. Versa, Jetta, March'), // Una pequeña ayuda visual para el mecánico

                \Filament\Forms\Components\TextInput::make('anio')
                    ->numeric()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('color')
                    ->datalist([
                        'Blanco', 'Negro', 'Gris', 'Plata', 'Rojo', 'Azul', 'Vino', 'Verde', 'Amarillo', 'Beige', 'Café'
                    ])
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('kilometraje')
                    ->numeric()
                    ->default(0),
                \Filament\Forms\Components\TextInput::make('tarjeta_circulacion')
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('poliza_seguro')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('placas')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('marca')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('anio')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculos::route('/'),
            'create' => Pages\CreateVehiculo::route('/create'),
            'edit' => Pages\EditVehiculo::route('/{record}/edit'),
        ];
    }
}
