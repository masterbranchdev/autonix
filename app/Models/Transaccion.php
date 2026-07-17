<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'transacciones';
    protected $guarded = [];

    protected $fillable = [
        'taller_id',
        'cotizacion_id',
        'tipo',
        'concepto',
        'monto',
        'metodo_pago',
        'referencia',
        'requiere_factura',
        'fecha',
        // Campos de Facturapi
        'factura_id',
        'estado_factura',
        'url_pdf_factura',
        'url_xml_factura'
    ];

    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
