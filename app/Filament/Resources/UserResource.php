<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Usuarios del Sistema';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?int $navigationSort = 2;

// Filtra la tabla para que los usuarios normales solo vean a los empleados de su propio taller
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Validamos con tu rol maestro
        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('taller_id', auth()->user()->taller_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Perfil')
                    ->description('Crea o edita las credenciales de acceso para el personal del taller.')
                    ->schema([

                        // Candado de seguridad: Auto-asigna el usuario al taller actual
                        Forms\Components\Hidden::make('taller_id')
                            ->default(auth()->user()->taller_id),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        // LÓGICA INTELIGENTE DE CONTRASEÑA:
                        // Si se está creando, es obligatoria. Si se está editando, solo se guarda si escriben algo nuevo.
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable() // Permite ver la contraseña al escribirla
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->placeholder(fn (string $context): string => $context === 'edit' ? 'Déjalo en blanco para mantener la contraseña actual' : '')
                            ->maxLength(255),

                        // ASIGNACIÓN DE ROLES CON FILAMENT SHIELD
                        Forms\Components\Select::make('roles')
                            ->label('Rol del Usuario')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),

                        Forms\Components\Toggle::make('activo')
                            ->label('Usuario Activo (Permitir acceso al sistema)')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false)
                            ->columnSpanFull(),

                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre Completo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                // Esta columna SIEMPRE se ve

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->visibleFrom('sm'), // Se oculta solo en celulares muy pequeños

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol Asignado')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->visibleFrom('md'), // Se oculta en celulares, visible desde tablets

                Tables\Columns\IconColumn::make('activo')
                    ->label('Acceso')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('taller.nombre_comercial')
                    ->label('Taller')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable()
                    ->default('Global / Autonix')
                    ->visibleFrom('md'), // Se oculta en celulares

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // --- NUEVO FILTRO POR TALLER ---
                Tables\Filters\SelectFilter::make('taller_id')
                    ->label('Filtrar por Taller')
                    ->relationship('taller', 'nombre_comercial')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado de Acceso')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Suspendidos / Bloqueados'),

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Opciones')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary')
                    ->button(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
