<?php

namespace App\Filament\Resources\InspeccionResource\Pages;

use App\Filament\Resources\InspeccionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInspeccion extends EditRecord
{
    protected static string $resource = InspeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
