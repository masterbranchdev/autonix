<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    protected $table = 'cotizacion_items';
    protected $guarded = [];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

}
