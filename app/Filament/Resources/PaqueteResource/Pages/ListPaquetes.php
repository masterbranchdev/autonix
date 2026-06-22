<?php

namespace App\Filament\Resources\PaqueteResource\Pages;

use App\Filament\Resources\PaqueteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaquetes extends ListRecords
{
    protected static string $resource = PaqueteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
