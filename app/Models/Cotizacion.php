<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $guarded = [];

    // Generar Folio Automático para la Cotización
    // Generar Folio Automático para la Cotización (A PRUEBA DE BALAS)
    protected static function booted()
    {
        static::creating(function ($cotizacion) {
            if (empty($cotizacion->folio)) {
                $year = now()->format('Y');
                $tallerId = $cotizacion->taller_id;

                // 1. Calculamos un punto de partida rápido
                $ultimaCotizacion = self::where('taller_id', $tallerId)
                    ->where('folio', 'like', "C{$tallerId}-{$year}-%")
                    ->orderBy('id', 'desc')
                    ->first();

                $consecutivo = 1;
                if ($ultimaCotizacion) {
                    $partes = explode('-', $ultimaCotizacion->folio);
                    $consecutivo = intval(end($partes)) + 1;
                }

                // 2. EL BLINDAJE DEFINITIVO: Verificamos en tiempo real si el folio existe.
                // Si la BD nos dice que ya hay uno (por la razón que sea), sumamos 1 y volvemos a preguntar.
                $folioPropuesto = "C{$tallerId}-{$year}-" . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);

                while (self::where('folio', $folioPropuesto)->exists()) {
                    $consecutivo++;
                    $folioPropuesto = "C{$tallerId}-{$year}-" . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
                }

                // 3. Asignamos el folio garantizado como único
                $cotizacion->folio = $folioPropuesto;
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
