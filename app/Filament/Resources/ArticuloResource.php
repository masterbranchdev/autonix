<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticuloResource\Pages;
use App\Filament\Resources\ArticuloResource\RelationManagers;
use App\Models\Articulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulo::class;

    protected static ?string $navigationGroup = 'Catálogos e Inventario';
    protected static ?string $navigationLabel = 'Catálogo (Refacciones / M.O.)';
    protected static ?string $modelLabel = 'Artículo';
    protected static ?string $pluralModelLabel = 'Artículos';
    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                \Filament\Forms\Components\Hidden::make('taller_id')->default(auth()->user()->taller_id),
                \Filament\Forms\Components\Select::make('tipo')
                    ->options(['Producto' => 'Producto (Refacción)', 'Servicio' => 'Servicio (Mano de obra)'])->required(),
                \Filament\Forms\Components\TextInput::make('nombre')->required(),
                \Filament\Forms\Components\TextInput::make('precio')->numeric()->prefix('$')->required(),
                \Filament\Forms\Components\Toggle::make('maneja_stock')->label('¿Llevar control de inventario?')->live(),
                \Filament\Forms\Components\TextInput::make('stock')->numeric()->default(0)
                    ->hidden(fn (\Filament\Forms\Get $get) => !$get('maneja_stock')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre / Descripción')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Producto' => 'info',
                        'Servicio' => 'success',
                        default => 'gray',
                    }),

                \Filament\Tables\Columns\TextColumn::make('precio')
                    ->label('Precio Base')
                    ->money('MXN')
                    ->sortable(),

                \Filament\Tables\Columns\IconColumn::make('maneja_stock')
                    ->label('¿Controla Stock?')
                    ->boolean(),

                \Filament\Tables\Columns\TextColumn::make('stock')
                    ->label('Existencias')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                // Aquí podrías agregar filtros más adelante (ej. Ver solo Servicios)
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
            'index' => Pages\ListArticulos::route('/'),
            'create' => Pages\CreateArticulo::route('/create'),
            'edit' => Pages\EditArticulo::route('/{record}/edit'),
        ];
    }
}
