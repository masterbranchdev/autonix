<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'fecha' => 'date',
    ];

    // Autogenerar Folio de Compra
    protected static function booted()
    {
        static::creating(function ($compra) {
            if (empty($compra->folio)) {
                $siguiente = self::where('taller_id', $compra->taller_id)->count() + 1;
                $compra->folio = 'COMP-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }
}
