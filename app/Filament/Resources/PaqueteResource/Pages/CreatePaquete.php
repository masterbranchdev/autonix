<?php

namespace App\Filament\Resources\PaqueteResource\Pages;

use App\Filament\Resources\PaqueteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaquete extends CreateRecord
{
    protected static string $resource = PaqueteResource::class;

    // Esta función inyecta el taller_id de forma 100% segura antes de guardar
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['taller_id'] = auth()->user()->taller_id;

        return $data;
    }
}
