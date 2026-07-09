<?php

namespace App\Observers;

use App\Models\Cotizacion;
use App\Models\Articulo;

class CotizacionObserver
{
    /**
     * 1. REGLA: Al crear una cotización, la O.S. pasa automáticamente a "Cotizando"
     */
    public function created(Cotizacion $cotizacion): void
    {
        if ($cotizacion->ordenServicio) {
            $cotizacion->ordenServicio->update(['estatus' => 'Cotizando']);
        }
    }

    /**
     * 2. REGLA: Blindaje de modificaciones. Se ejecuta ANTES de guardar cambios.
     */
    public function updating(Cotizacion $cotizacion): void
    {
        // Si el usuario modifica las piezas, el descuento o el IVA,
        // y NO está intentando cambiar el estatus en este preciso momento...
        if ($cotizacion->isDirty(['items', 'descuento', 'aplicar_iva']) && !$cotizacion->isDirty('estatus')) {
            // ... forzamos a que regrese a Borrador por haber sido alterada.
            $cotizacion->estatus = 'Borrador';
        }
    }

    /**
     * 3. REGLA: Aprobación y Control de Almacén. Se ejecuta DESPUÉS de guardar cambios.
     */
    public function updated(Cotizacion $cotizacion): void
    {
        // ¿El estatus acaba de cambiar a "Aprobada"?
        if ($cotizacion->isDirty('estatus') && $cotizacion->estatus === 'Aprobada') {
            $this->descontarStock($cotizacion);

            // Automáticamente la O.S. pasa a "En Reparación"
            if ($cotizacion->ordenServicio) {
                $cotizacion->ordenServicio->update(['estatus' => 'En Reparación']);
            }
        }

        // ¿El estatus pasó de "Aprobada" a "Borrador" (por modificación) o "Rechazada"?
        if ($cotizacion->isDirty('estatus') && $cotizacion->getOriginal('estatus') === 'Aprobada' && $cotizacion->estatus !== 'Aprobada') {
            // Regresamos las piezas al almacén
            $this->devolverStock($cotizacion);
        }
    }

    public function deleting(Cotizacion $cotizacion): void
    {
        if ($cotizacion->estatus === 'Aprobada') {
            $this->devolverStock($cotizacion);
        }
    }

    // --- FUNCIONES INVISIBLES DE CONTROL DE ALMACÉN ---

    private function descontarStock(Cotizacion $cotizacion): void
    {
        foreach ($cotizacion->items as $item) {
            if (!empty($item['articulo_id'])) {
                $articulo = Articulo::find($item['articulo_id']);
                if ($articulo && $articulo->maneja_stock) {
                    $articulo->decrement('stock', $item['cantidad'] ?? 1);
                }
            }
        }
    }

    private function devolverStock(Cotizacion $cotizacion): void
    {
        foreach ($cotizacion->items as $item) {
            if (!empty($item['articulo_id'])) {
                $articulo = Articulo::find($item['articulo_id']);
                if ($articulo && $articulo->maneja_stock) {
                    $articulo->increment('stock', $item['cantidad'] ?? 1);
                }
            }
        }
    }
}
