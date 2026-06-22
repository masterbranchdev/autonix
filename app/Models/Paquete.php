<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paquete extends Model {
    protected $guarded = [];
    protected $casts = [ 'items' => 'array' ]; // Transforma el JSON a arreglo automáticamente
    public function taller() { return $this->belongsTo(Taller::class); }
}
