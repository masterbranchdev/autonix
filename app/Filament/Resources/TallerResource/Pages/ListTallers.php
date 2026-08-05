<?php

namespace App\Filament\Resources\TallerResource\Pages;

use App\Filament\Resources\TallerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTallers extends ListRecords
{
    protected static string $resource = TallerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AutonixStatsWidget::class,
            \App\Filament\Widgets\TalleresChartWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Este es el botón maestro que te abre el formulario de creación
            Actions\CreateAction::make()
                ->label('Nuevo Taller')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

}
