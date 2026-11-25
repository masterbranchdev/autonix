<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use App\Models\Cliente;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrdenServicio extends EditRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function sinCliente(): bool
    {
        return $this->record->vehiculo && ! $this->record->vehiculo->cliente_id;
    }

    // Al abrir la orden, si el vehículo no tiene cliente, abre el modal automáticamente
    protected function afterFill(): void
    {
        if ($this->sinCliente()) {
            $this->mountAction('asignarCliente');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('asignarCliente')
                ->label('Asignar cliente')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn () => $this->sinCliente())
                ->modalHeading('Vehículo sin cliente')
                ->modalDescription('Este vehículo no tiene un cliente asignado. Asigna uno existente o crea uno nuevo para poder enviar notificaciones y generar documentos.')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('warning')
                ->modalSubmitActionLabel('Asignar')
                ->modalCancelActionLabel('Más tarde')
                ->form([
                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->options(fn () => Cliente::where('taller_id', auth()->user()->taller_id)
                            ->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('telefono')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('correo')
                                ->email()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(fn (array $data) => Cliente::create([
                            'taller_id' => auth()->user()->taller_id,
                            'nombre' => \Illuminate\Support\Str::ascii($data['nombre']),
                            'telefono' => $data['telefono'] ?? null,
                            'correo' => $data['correo'] ?? null,
                        ])->getKey()),
                ])
                ->action(function (array $data) {
                    $this->record->vehiculo->update(['cliente_id' => $data['cliente_id']]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Cliente asignado al vehículo')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
