<?php

namespace App\Filament\Resources\CotizacionResource\Pages;

use App\Filament\Resources\CotizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCotizacion extends EditRecord
{
    protected static string $resource = CotizacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // --- EL CANDADO DE SEGURIDAD PARA EDICIONES ---
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Comparamos el estatus actual de la base de datos con el que viene en el formulario.
        // Si son iguales, significa que el usuario entró a editar piezas, precios o notas
        // y NO a cambiar explícitamente el estatus.
        if ($this->record->estatus === $data['estatus']) {

            // Forzamos el estatus a Borrador por haber sido alterada
            $data['estatus'] = 'Borrador';

            // Notificamos al usuario visualmente
            \Filament\Notifications\Notification::make()
                ->title('Cotización alterada')
                ->body('El estatus regresó a Borrador debido a las modificaciones.')
                ->warning()
                ->send();
        }

        return $data;
    }
}
