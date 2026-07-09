<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    protected $guarded = [];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenesServicio()
    {
        // Un vehículo puede tener muchas órdenes de servicio en su historia
        return $this->hasMany(OrdenServicio::class);
    }

}
