<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articulo extends Model {
    protected $guarded = [];


    protected $fillable = [
        'taller_id',
        'tipo',
        'nombre',
        'clave_sat',
        'unidad_sat',
        'precio',
        'maneja_stock',
        'stock',
    ];

    public function taller() { return $this->belongsTo(Taller::class); }
}
