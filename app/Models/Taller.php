<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taller extends Model
{
    // 1. Le decimos el nombre exacto de la tabla en español
    protected $table = 'talleres';

    // 2. Permitimos insertar datos masivamente (como lo hace el Seeder)
    protected $guarded = [];

    // 3. Dejamos lista la relación: Un taller tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }
}
