<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoleResource extends BaseRoleResource
{
    // Cambiamos la URL interna para no chocar con el paquete original
    protected static ?string $slug = 'roles-sistema';

    // Le asignamos un nombre en el menú
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Roles';
    protected static ?string $modelLabel = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        // Forzamos a que ESTE recurso sí aparezca en el menú,
        // ignorando la configuración que apagó al original.
        return true;
    }

    // --- 1. INTERCEPTAMOS EL FORMULARIO ORIGINAL DE SHIELD ---
    public static function form(Form $form): Form
    {
        // Obtenemos el formulario original con todos los permisos y pestañas de Shield
        $form = parent::form($form);

        // Extraemos sus componentes visuales
        $components = $form->getComponents();

        // Mandamos a buscar el campo 'name' para inyectarle nuestra regla
        static::inyectarValidacionMultitenant($components);

        // Devolvemos el formulario ya corregido
        return $form->schema($components);
    }

    // --- 2. FUNCIÓN RECURSIVA PARA ENCONTRAR Y CORREGIR EL CAMPO ---
    protected static function inyectarValidacionMultitenant(array $components): void
    {
        foreach ($components as $component) {
            // Si encontramos el campo de texto llamado 'name'...
            if ($component instanceof \Filament\Forms\Components\TextInput && $component->getName() === 'name') {

                // Sobrescribimos su regla única para que solo busque dentro del mismo taller
                $component->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule) {
                        // Si eres el SuperAdmin, no te restringimos. Si eres un Taller, filtramos por tu ID.
                        $tallerId = auth()->user()->hasRole('super_admin')
                            ? null
                            : auth()->user()->taller_id;

                        // Si hay un ID de taller, aplicamos el filtro a la validación
                        if ($tallerId) {
                            return $rule->where('taller_id', $tallerId);
                        }
                        return $rule;
                    }
                );
            }
            // Si es un contenedor (como una Sección o un Grid), buscamos adentro
            elseif (method_exists($component, 'getChildComponents')) {
                static::inyectarValidacionMultitenant($component->getChildComponents());
            }
        }
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
                // Esta columna SIEMPRE se ve

                Tables\Columns\TextColumn::make('taller.nombre_comercial')
                    ->label('Taller')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->default('Global / Autonix')
                    ->visibleFrom('sm'), // Se oculta en celulares muy pequeños

                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label('Permisos')
                    ->counts('permissions')
                    ->colors(['success'])
                    ->visibleFrom('md'), // Se oculta en celulares

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime('d M Y, H:i:s')
                    ->visibleFrom('lg'), // Solo visible en computadoras
            ])
            ->filters([
                // --- FILTRO POR TALLER ---
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

    // --- CONECTAMOS NUESTRAS NUEVAS PÁGINAS ---
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

}
