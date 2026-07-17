<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationGroup = 'Directorio';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Campo oculto para el SaaS
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                \Filament\Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('telefono')
                    ->tel()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('correo')
                    ->email()
                    ->maxLength(255),

                // --- NUEVA SECCIÓN: FACTURACIÓN CFDI 4.0 ---
                \Filament\Forms\Components\Section::make('Datos Fiscales (CFDI 4.0)')
                    ->description('Requeridos solo si el cliente solicitará factura.')
                    ->collapsed() // La dejamos cerrada por defecto para no hacer largo el formulario
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('rfc')
                            ->label('RFC')
                            ->maxLength(13)
                            ->placeholder('Ej. XAXX010101000')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;']),

                        \Filament\Forms\Components\TextInput::make('razon_social')
                            ->label('Razón Social / Nombre Completo')
                            ->helperText('Tal como aparece en su Constancia de Situación Fiscal (Sin SA de CV)'),

                        \Filament\Forms\Components\TextInput::make('codigo_postal')
                            ->label('Código Postal')
                            ->maxLength(5)
                            ->numeric(),

                        \Filament\Forms\Components\Select::make('regimen_fiscal')
                            ->label('Régimen Fiscal')
                            ->searchable()
                            ->options([
                                '601' => '601 - General de Ley Personas Morales',
                                '603' => '603 - Personas Morales con Fines no Lucrativos',
                                '605' => '605 - Sueldos y Salarios e Ingresos Asimilados',
                                '606' => '606 - Arrendamiento',
                                '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                                '626' => '626 - Régimen Simplificado de Confianza (RESICO)',
                            ]),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
