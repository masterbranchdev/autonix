<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Recordatorio;
use Filament\Tables\Grouping\Group;
use Carbon\Carbon;

class AgendaNativaWidget extends BaseWidget
{
    // Ocupamos todo el ancho del panel
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Recordatorio::query()
                    ->where('taller_id', auth()->user()->taller_id)
                    ->where('estatus', 'Cita Agendada') // Solo mostramos citas confirmadas
                    ->whereNotNull('fecha_hora_cita')
                    // Filtramos de hoy en adelante para no ver el pasado
                    ->whereDate('fecha_hora_cita', '>=', now()->toDateString())
                    ->orderBy('fecha_hora_cita', 'asc') // Orden cronológico
            )
            ->heading('🗓️ Itinerario de Citas Confirmadas')
            ->description('Listado de clientes programados para ingresar al taller en los próximos días.')

            // LA MAGIA: Agrupamos las filas visualmente por día
            ->defaultGroup(
                Group::make('fecha_hora_cita')
                    ->label('Citas para el día')
                    ->date('l, d \d\e F \d\e Y') // Formato elegante (Ej. Monday, 10 de July de 2026)
                    ->collapsible()
            )
            ->columns([
                Tables\Columns\TextColumn::make('fecha_hora_cita')
                    ->label('Hora')
                    ->time('h:i A')
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold')
                    // Lo que dijo el cliente se anida aquí abajo
                    ->description(fn (Recordatorio $record) => $record->observaciones_seguimiento)
                    // El ícono nativo de Filament se aplica a la columna
                    ->icon(fn (Recordatorio $record) => $record->observaciones_seguimiento ? 'heroicon-m-chat-bubble-oval-left-ellipsis' : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->description(fn (Recordatorio $record) => "{$record->vehiculo->marca} {$record->vehiculo->modelo}")
                    ->searchable(),

                Tables\Columns\TextColumn::make('motivo')
                    ->label('Servicio Programado')
                    ->wrap()
                    // Las notas internas del taller se anidan aquí abajo
                    ->description(fn (Recordatorio $record) => $record->notas_internas)
                    // El ícono nativo de Filament se aplica a la columna
                    ->icon(fn (Recordatorio $record) => $record->notas_internas ? 'heroicon-m-clipboard-document-list' : null),
            ])
            ->actions([
                // Botón de WhatsApp especializado en confirmación de asistencia
                Tables\Actions\Action::make('whatsapp_recordatorio')
                    ->label('Recordar Cita')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->url(function (Recordatorio $record) {
                        $cliente = $record->cliente;
                        $taller = $record->taller;

                        $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);
                        if (strlen($telefono) == 10) { $telefono = '52' . $telefono; }

                        $nombre = trim($cliente->nombre);
                        $hora = $record->fecha_hora_cita->format('h:i A');
                        $fecha = $record->fecha_hora_cita->isToday() ? 'el día de hoy' : 'el ' . $record->fecha_hora_cita->format('d/m/Y');

                        $nombreTaller = $taller->nombre_comercial ?? 'tu taller de confianza';
                        $domicilio = $taller->domicilio ?? 'nuestras instalaciones';

                        // Mensaje diseñado para que el cliente no falte a su cita
                        $mensaje = "¡Hola *{$nombre}*! 👨‍🔧 Te saludamos de *{$nombreTaller}*. Solo pasamos a confirmarte que tenemos todo listo para recibir tu vehículo {$fecha} a las *{$hora}* en *{$domicilio}*. ¡Te esperamos!";

                        return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                    })
                    ->openUrlInNewTab(),

                // Botón para acceder rápido a los detalles si el asesor necesita leer las notas
                Tables\Actions\Action::make('ver_detalles')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Recordatorio $record) => \App\Filament\Resources\RecordatorioResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
