<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $guarded = [];

    // Generar Folio Automático para la Cotización
    protected static function booted()
    {
        static::creating(function ($cotizacion) {
            if (empty($cotizacion->folio)) {
                $anioActual = now()->year;
                $totalAnio = self::where('taller_id', $cotizacion->taller_id)
                    ->whereYear('created_at', $anioActual)
                    ->count();

                $siguiente = $totalAnio + 1;
                // Formato de cotización: C[ID]-AÑO-CONSECUTIVO
                $cotizacion->folio = 'C' . $cotizacion->taller_id . '-' . $anioActual . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class);
    }

    public function items()
    {
        return $this->hasMany(CotizacionItem::class);
    }
}
