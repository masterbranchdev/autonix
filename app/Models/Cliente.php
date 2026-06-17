<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $guarded = []; // Permite asignación masiva

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}
