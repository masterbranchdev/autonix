<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';
    protected $guarded = [];

    protected $casts = [
        'inventario' => 'array',
        'ingreso_grua' => 'boolean',
        'testigos' => 'array',           // <--- NUEVO
        'danios_carroceria' => 'array',  // <--- NUEVO
        'fecha_ingreso' => 'datetime',
    ];

// LA MAGIA DEL FOLIO AUTOMÁTICO (Globalmente Único por Taller)
    protected static function booted()
    {
        static::creating(function ($orden) {

            if (empty($orden->token_url)) {
                $orden->token_url = Str::random(32);
            }

            // Solo lo generamos si viene vacío
            if (empty($orden->folio)) {
                $anioActual = now()->year;

                // Contamos cuántas órdenes tiene ESTE taller en el año actual
                $totalOrdenesAnio = self::where('taller_id', $orden->taller_id)
                    ->whereYear('created_at', $anioActual)
                    ->count();

                $siguiente = $totalOrdenesAnio + 1;

                // Formato: T[ID]-AÑO-CONSECUTIVO (Ej. T1-2026-0001)
                $orden->folio = 'T' . $orden->taller_id . '-' . $anioActual . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function cotizaciones()
    {
        // Una Orden de Servicio puede tener muchas cotizaciones
        return $this->hasMany(Cotizacion::class);
    }

    public function inspecciones()
    {
        // Una orden de servicio puede tener múltiples registros de inspección
        return $this->hasMany(Inspeccion::class);
    }


}
