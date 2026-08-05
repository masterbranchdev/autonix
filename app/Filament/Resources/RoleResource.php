<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class RoleResource extends BaseRoleResource
{
    protected static ?string $slug = 'roles-sistema';
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Roles';
    protected static ?string $modelLabel = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    // --- FORMULARIO CON ESQUEMA PROPIO (Evita el conflicto de Shield) ---
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                // Campo Name propio sin validaciones globales de Shield
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->required()
                                    ->maxLength(255)
                                    // Bloqueo de seguridad anti-super_admin
                                    ->rules([
                                        function () {
                                            return function (string $attribute, mixed $value, \Closure $fail) {
                                                if (!auth()->user()->hasRole('super_admin') && preg_match('/super[\s_]*admin/i', $value)) {
                                                    $fail('Por seguridad del sistema, no tienes permisos para crear roles de nivel administrativo.');
                                                }
                                            };
                                        },
                                    ]),

                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(\BezhanSalleh\FilamentShield\Support\Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                \BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(fn (): HtmlString => new HtmlString(__('filament-shield::filament-shield.field.select_all.message')))
                                    ->dehydrated(fn (bool $state): bool => $state),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                // Mantenemos la carga de permisos intacta para que CreateRole no falle
                static::getShieldFormComponents(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->label('Nombre del Rol')
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->colors(['primary'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('taller.nombre_comercial')
                    ->label('Taller')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->default('Global / Autonix')
                    ->visibleFrom('sm'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label('Permisos')
                    ->counts('permissions')
                    ->colors(['success'])
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime('d M Y, H:i:s')
                    ->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('taller_id')
                    ->label('Filtrar por Taller')
                    ->relationship('taller', 'nombre_comercial')
                    ->searchable()
                    ->preload(),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
