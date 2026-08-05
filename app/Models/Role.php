<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole
{
    protected static function booted()
    {
        // 1. FILTRO DE LECTURA (Ignoramos al SuperAdmin)
        static::addGlobalScope('taller', function (Builder $builder) {
            if (auth()->check() && auth()->user()->taller_id && auth()->user()->email !== 'admin@autonix.com.mx') {
                $builder->where('taller_id', auth()->user()->taller_id);
            }
        });

        // 2. FILTRO DE CREACIÓN
        static::creating(function ($role) {
            if (empty($role->taller_id) && auth()->check() && auth()->user()->taller_id) {
                $role->taller_id = auth()->user()->taller_id;
            }
        });
    }

    // --- NUEVO: RELACIÓN CON EL TALLER ---
    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }
}
