<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Role extends SpatieRole
{
    protected static function booted()
    {
        // 1. FILTRO DE LECTURA (Ignoramos al SuperAdmin)
        static::addGlobalScope('taller', function (Builder $builder) {
            if (auth()->check() && auth()->user()->taller_id && !auth()->user()->hasRole('super_admin')) {
                $builder->where('taller_id', auth()->user()->taller_id);
            }
        });

        // 2. FILTRO DE CREACIÓN Y ASIGNACIÓN AUTOMÁTICA DE TALLER
        static::creating(function ($role) {
            if (empty($role->taller_id) && auth()->check() && !auth()->user()->hasRole('super_admin')) {
                $role->taller_id = auth()->user()->taller_id;
            }

            // Validación preventiva en creación
            $exists = self::where('name', $role->name)
                ->where('guard_name', $role->guard_name ?? 'web')
                ->where('taller_id', $role->taller_id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'data.name' => ['El nombre de este rol ya está registrado en tu taller.'],
                ]);
            }
        });

        // 3. NUEVO: FILTRO DE ACTUALIZACIÓN (EDIT)
        static::updating(function ($role) {
            // Verificamos si ya existe otro rol con el mismo nombre en el mismo taller (excluyendo el registro actual)
            $exists = self::where('name', $role->name)
                ->where('guard_name', $role->guard_name ?? 'web')
                ->where('taller_id', $role->taller_id)
                ->where('id', '!=', $role->id) // Ignoramos el rol que estamos editando
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
