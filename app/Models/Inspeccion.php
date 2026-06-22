<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspeccion extends Model
{
    protected $table = 'inspecciones';
    protected $guarded = [];

    protected $casts = [
        'check_puntos' => 'array',
        'llantas' => 'array',
        'balatas' => 'array',
        'adicionales' => 'array',
        'bateria' => 'array',
    ];

    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class);
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

}
