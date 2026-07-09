<?php

namespace App\Filament\Resources\InspeccionResource\Pages;

use App\Filament\Resources\InspeccionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInspeccion extends CreateRecord
{
    protected static string $resource = InspeccionResource::class;

    // Se ejecuta automáticamente un milisegundo después de guardar la inspección
    protected function afterCreate(): void
    {
        $inspeccion = $this->record;

        // Cambiamos el estatus de la O.S. a "Listo" como solicitaste
        if ($inspeccion->ordenServicio) {
            $inspeccion->ordenServicio->update(['estatus' => 'Listo']);
        }
    }
}
