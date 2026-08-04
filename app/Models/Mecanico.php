<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Mecanico extends Model {
    protected $guarded = [];

    public function taller() {
        return $this->belongsTo(Taller::class);
    }

    public function ordenesServicio() {
        return $this->belongsToMany(OrdenServicio::class, 'mecanico_orden_servicio');
    }
}
