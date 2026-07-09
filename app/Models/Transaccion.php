<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'transacciones';
    protected $guarded = [];

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
