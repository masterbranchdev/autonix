<?php

namespace App\Filament\Resources\TallerResource\Pages;

use App\Filament\Resources\TallerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Permission;

class CreateTaller extends CreateRecord
{
    protected static string $resource = TallerResource::class;

    protected function afterCreate(): void
    {
        $taller = $this->record;

        // 1. Limpiamos el nombre comercial para crear un dominio válido
        $dominioLimpio = Str::slug($taller->nombre_comercial, '');

        // 2. Doble validación de seguridad
        if (str_contains(strtolower($dominioLimpio), 'autonix')) {
            $dominioLimpio = 'taller' . $taller->id;
        }

        $emailGenerado = "admin@{$dominioLimpio}.com";

        // 3. Generamos una contraseña temporal segura
        $passwordTemporal = Str::password(8, true, true, false, false);

        // 4. Creamos el usuario maestro de este taller (Sin Hash::make)
        $user = User::create([
            'name' => 'Admin ' . $taller->nombre_comercial,
            'email' => $emailGenerado,
            'password' => $passwordTemporal, // <-- ENVIAMOS TEXTO PLANO
            'taller_id' => $taller->id,
            'rol' => 'admin',
        ]);

        // --- 5. MAGIA DE ROLES Y PERMISOS MULTITENANT (SPATIE) ---
        $rolAdmin = \App\Models\Role::firstOrCreate([
            'name' => 'Admin Taller',
            'guard_name' => 'web',
            'taller_id' => $taller->id, // Inyectamos explícitamente a quién pertenece
        ]);

        // Obtenemos todos los permisos excluyendo los del SaaS
        $permisosPermitidos = Permission::where('name', 'not like', '%_taller')->get();

        // Sincronizamos los permisos y asignamos el rol
        $rolAdmin->syncPermissions($permisosPermitidos);
        $user->assignRole($rolAdmin);
        // ----------------------------------------------

        // 6. Mostramos la alerta en pantalla
        Notification::make()
            ->title('🚀 Taller y Administrador Creados')
            ->body(new HtmlString("
                El taller se configuró correctamente. Entrégale estos accesos a tu cliente:<br><br>
                <strong>Usuario:</strong> {$emailGenerado}<br>
                <strong>Contraseña:</strong> {$passwordTemporal}
            "))
            ->success()
            ->persistent()
            ->send();
    }
}
