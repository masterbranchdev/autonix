<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\ListRoles as ShieldListRoles;
use Filament\Tables\Table; // <-- Importante agregar esto

class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;

    public function table(Table $table): Table
    {
        return static::getResource()::table($table);
    }
}
