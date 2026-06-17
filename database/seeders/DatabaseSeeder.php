<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Taller; // Importamos el modelo Taller
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos el Taller Maestro
        $taller = Taller::create([
            'nombre_comercial' => 'Autonix Central',
            'plan' => 'anual',
            'activo' => true,
        ]);

        // 2. Creamos tu Usuario amarrado a ese taller
        User::create([
            'taller_id' => $taller->id,
            'name' => 'Admin',
            'email' => 'admin@autonix.com.mx',
            'password' => Hash::make('12345678'), // La contraseña será: password
            'rol' => 'admin',
            'activo' => true,
        ]);
    }
}
