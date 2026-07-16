<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taller extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_comercial',
        'plan',
        'activo',
        'fecha_suscripcion',
        'vencimiento_suscripcion',
        'twilio_sid',
        'twilio_token',
        'twilio_whatsapp',
        'twilio_tpl_estatus',
        'twilio_tpl_cotizacion',
        'twilio_tpl_recordatorio',
        'logo_path',
        'telefono',
        'whatsapp_publico',
        'domicilio',
        'horario_atencion',
        'openai_api_key',
        'limite_ia_mensual',
        'consumo_ia_mes'
    ];

    // 1. Le decimos el nombre exacto de la tabla en español
    protected $table = 'talleres';

    // 2. Permitimos insertar datos masivamente (como lo hace el Seeder)
    protected $guarded = [];

    // 3. Dejamos lista la relación: Un taller tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }
}
