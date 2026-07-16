<?php

namespace App\Filament\Resources\TallerResource\Pages;

use App\Filament\Resources\TallerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaller extends EditRecord
{
    protected static string $resource = TallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
