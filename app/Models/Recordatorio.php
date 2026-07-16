<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recordatorio extends Model
{
    use HasFactory;

    protected $table = 'recordatorios';

    protected $fillable = [
        'taller_id',
        'cliente_id',
        'vehiculo_id',
        'orden_servicio_id',
        'fecha_programada',
        'motivo',
        'nivel_importancia',
        'notas_internas',
        'estatus',
        'fecha_hora_cita',             // NUEVO
        'observaciones_seguimiento',   // NUEVO
        'enviar_whatsapp_automatico',
        'whatsapp_enviado_at',
        'whatsapp_job_id',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_hora_cita' => 'datetime', // Transforma la fecha y hora correctamente
        'whatsapp_enviado_at' => 'datetime',
        'enviar_whatsapp_automatico' => 'boolean',
    ];

    // Relaciones
    public function taller()
    {
        return $this->belongsTo(Taller::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class);
    }
}
