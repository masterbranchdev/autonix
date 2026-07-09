<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseWidget;

class WelcomeWidget extends BaseWidget
{
    // Evita que Filament lo cargue automáticamente en el centro de la pantalla
    protected static bool $isDiscovered = false;

    // Hace que ocupe todo el ancho
    protected int | string | array $columnSpan = 'full';
}
