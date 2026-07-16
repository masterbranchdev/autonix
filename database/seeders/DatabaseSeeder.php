<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Taller; // Importamos el modelo Taller
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
// 1. Creamos el Taller Maestro de Autonix[cite: 14]
        $taller = Taller::create([
            'nombre_comercial' => 'Autonix Central (SaaS)',
            'plan' => 'premium',
            'activo' => true,
        ]);

        // 2. Creamos al usuario Dios (Dueño del SaaS)[cite: 14]
        $admin = User::create([
            'taller_id' => $taller->id,
            'name' => 'CEO Autonix',
            'email' => 'admin@autonix.com.mx', // ESTA SERÁ NUESTRA LLAVE MAESTRA[cite: 14]
            'password' => Hash::make('password'),
            // Eliminamos la línea 'rol' => 'admin' porque Filament usa una tabla separada[cite: 14]
        ]);

        // 3. Generamos el rol de Shield y se lo inyectamos correctamente
        $rolMaster = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin->assignRole($rolMaster);
    }
}
