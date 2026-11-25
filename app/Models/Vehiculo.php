<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    protected $guarded = [];

    protected static function booted(): void
    {
        // Un vehículo nunca se crea sin cliente
        static::creating(function (Vehiculo $vehiculo) {
            if (empty($vehiculo->cliente_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cliente_id' => 'Debes asignar un cliente al vehículo.',
                ]);
            }
        });
    }

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
