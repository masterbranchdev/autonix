<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ListRoles as ShieldListRoles;
use Filament\Tables\Table; // <-- Importante agregar esto

class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;

    // --- EL GOLPE MAESTRO ---
    // Anulamos la tabla programada por el paquete original y obligamos a la página
    // a usar exclusivamente la tabla que programamos en nuestro RoleResource.php
    public function table(Table $table): Table
    {
        return static::getResource()::table($table);
    }
}
