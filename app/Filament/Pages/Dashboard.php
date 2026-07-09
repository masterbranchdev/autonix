<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    // LA MAGIA: Esto coloca el widget en el encabezado absoluto de la página
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeWidget::class,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtro de Periodo')
                    ->description('Selecciona un rango de fechas para calcular los ingresos y egresos')
                    ->schema([
                        DatePicker::make('fecha_inicio')
                            ->label('Desde la fecha:')
                            ->default(now()->startOfMonth())
                            ->live(),

                        DatePicker::make('fecha_fin')
                            ->label('Hasta la fecha:')
                            ->default(now()->endOfMonth())
                            ->live(),
                    ])
                    ->columns(2),
            ]);
    }
}
