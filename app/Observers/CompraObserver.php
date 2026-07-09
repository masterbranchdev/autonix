<?php

namespace App\Observers;

use App\Models\Compra;
use App\Models\Articulo;
use App\Models\Transaccion;

class CompraObserver
{
    public function created(Compra $compra): void
    {
        // 1. INGRESAR PIEZAS AL ALMACÉN
        if (is_array($compra->items)) {
            foreach ($compra->items as $item) {
                if (!empty($item['articulo_id'])) {
                    $articulo = Articulo::find($item['articulo_id']);
                    // Si el artículo maneja stock, le sumamos las piezas compradas
                    if ($articulo && $articulo->maneja_stock) {
                        $articulo->increment('stock', $item['cantidad']);
                    }
                }
            }
        }

        // 2. REGISTRAR EL GASTO FINANCIERO (Egreso)
        Transaccion::create([
            'taller_id' => $compra->taller_id,
            'tipo' => 'Egreso',
            'concepto' => 'Compra a proveedor: ' . ($compra->proveedor ?? 'General') . ' (Folio: ' . $compra->folio . ')',
            'monto' => $compra->total,
            'fecha' => $compra->fecha,
        ]);
    }
}
