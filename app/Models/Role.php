<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; // <-- Necesario para la consulta directa

class Role extends SpatieRole
{
    protected static function booted()
    {
        // 1. FILTRO DE LECTURA (Ignoramos al SuperAdmin con consulta directa a DB)
        static::addGlobalScope('taller', function (Builder $builder) {
            if (auth()->check() && auth()->user()->taller_id) {

                // Verificación segura DIRECTA a la BD para evitar el loop infinito de Spatie
                $isSuperAdmin = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_id', auth()->id())
                    ->where('roles.name', 'super_admin')
                    ->exists();

                if (!$isSuperAdmin) {
                    $builder->where('roles.taller_id', auth()->user()->taller_id);
                }
            }
        });

        // 2. FILTRO DE CREACIÓN Y ASIGNACIÓN AUTOMÁTICA DE TALLER
        static::creating(function ($role) {
            if (auth()->check()) {
                $isSuperAdmin = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_id', auth()->id())
                    ->where('roles.name', 'super_admin')
                    ->exists();

                if (empty($role->taller_id) && auth()->user()->taller_id && !$isSuperAdmin) {
                    $role->taller_id = auth()->user()->taller_id;
                }
            }

            // Validación preventiva en creación
            $exists = self::withoutGlobalScope('taller') // Quitamos el scope para validar bien
            ->where('name', $role->name)
                ->where('guard_name', $role->guard_name ?? 'web')
                ->where('taller_id', $role->taller_id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'data.name' => ['El nombre de este rol ya está registrado en tu taller.'],
                ]);
            }
        });

        // 3. FILTRO DE ACTUALIZACIÓN (EDIT)
        static::updating(function ($role) {
            $exists = self::withoutGlobalScope('taller') // Quitamos el scope para validar bien
            ->where('name', $role->name)
                ->where('guard_name', $role->guard_name ?? 'web')
                ->where('taller_id', $role->taller_id)
                ->where('id', '!=', $role->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'data.name' => ['El nombre de este rol ya está registrado en tu taller.'],
                ]);
            }
        });
    }

    // --- RELACIÓN CON EL TALLER ---
    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }
}
