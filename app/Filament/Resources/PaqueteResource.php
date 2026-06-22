<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaqueteResource\Pages;
use App\Filament\Resources\PaqueteResource\RelationManagers;
use App\Models\Paquete;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaqueteResource extends Resource
{
    protected static ?string $model = Paquete::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                \Filament\Forms\Components\Hidden::make('taller_id')->default(auth()->user()->taller_id),
                \Filament\Forms\Components\TextInput::make('nombre')->label('Nombre del Paquete (Ej. Servicio Menor)')->required()->columnSpanFull(),
                \Filament\Forms\Components\Textarea::make('descripcion')->label('Descripción')->columnSpanFull(),

                // El creador de paquetes
                \Filament\Forms\Components\Repeater::make('items')
                    ->label('Artículos y Servicios del Paquete')
                    ->schema([
                        \Filament\Forms\Components\Select::make('articulo_id')
                            ->label('Producto/Servicio')
                            ->options(fn() => \App\Models\Articulo::where('taller_id', auth()->user()->taller_id)->pluck('nombre', 'id'))
                            ->required()->searchable()->live()
                            // Auto-carga el precio original, pero deja que el mecánico lo edite para el paquete
                            ->afterStateUpdated(function($state, \Filament\Forms\Set $set) {
                                $art = \App\Models\Articulo::find($state);
                                if($art) $set('precio_especial', $art->precio);
                            })->columnSpan(2),
                        \Filament\Forms\Components\TextInput::make('cantidad')->numeric()->default(1)->required()->columnSpan(1),
                        \Filament\Forms\Components\TextInput::make('precio_especial')->label('Precio en Paquete (0 = Cortesía)')->numeric()->prefix('$')->required()->columnSpan(1),
                    ])->columns(4)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPaquetes::route('/'),
            'create' => Pages\CreatePaquete::route('/create'),
            'edit' => Pages\EditPaquete::route('/{record}/edit'),
        ];
    }
}
