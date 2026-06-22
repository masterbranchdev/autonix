<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenServicio extends EditRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
