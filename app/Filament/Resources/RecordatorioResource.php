<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordatorioResource\Pages;
use App\Models\Recordatorio;
use App\Models\OrdenServicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecordatorioResource extends Resource
{
    protected static ?string $model = Recordatorio::class;

    protected static ?string $navigationGroup = 'Operación del Taller';
    protected static ?string $modelLabel = 'Próximo Servicio';
    protected static ?string $pluralModelLabel = 'Próximos Servicios';
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                \Filament\Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload() // Carga la lista de clientes inmediatamente al dar clic
                    ->live() // Vuelve el campo "reactivo" en tiempo real
                    ->afterStateUpdated(fn (callable $set) => $set('vehiculo_id', null)) // Si cambias de cliente, borra el auto seleccionado
                    ->required(),

                \Filament\Forms\Components\Select::make('vehiculo_id')
                    ->label('Vehículo')
                    ->relationship(
                        name: 'vehiculo',
                        titleAttribute: 'id',
                        // EL FILTRO MÁGICO: Solo busca vehículos que pertenezcan al cliente seleccionado arriba
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, \Filament\Forms\Get $get) => $query->where('cliente_id', $get('cliente_id'))
                    )
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Vehiculo $record) => trim("{$record->marca} {$record->modelo} " . ($record->placas ? "- Placas: {$record->placas}" : '')))
                    ->searchable(['marca', 'modelo', 'placas'])
                    ->preload()
                    ->required(),

                \Filament\Forms\Components\DatePicker::make('fecha_programada')
                    ->label('¿Cuándo le toca regresar?')
                    ->required(),

                \Filament\Forms\Components\TextInput::make('motivo')
                    ->label('Servicio sugerido (Motivo)')
                    ->placeholder('Ej. Cambio de balatas, Afinación...')
                    ->required()
                    ->columnSpanFull(),

                \Filament\Forms\Components\Select::make('nivel_importancia')
                    ->options([
                        'Baja' => 'Baja (Estética, lavado)',
                        'Media' => 'Media (Mantenimiento preventivo)',
                        'Alta' => 'Alta (Frenos, seguridad, motor)',
                    ])
                    ->default('Media')
                    ->required(),

                \Filament\Forms\Components\Select::make('estatus')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Contactado' => 'Contactado',
                        'Cita Agendada' => 'Cita Agendada',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
                    ])
                    ->default('Pendiente')
                    ->required(),

                \Filament\Forms\Components\Textarea::make('notas_internas')
                    ->label('Notas internas (Para el taller)')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('fecha_programada')
                    ->label('Fecha Sugerida')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->description(function (Recordatorio $record) {
                        // Calcula las visitas históricas para medir fidelización
                        $visitas = OrdenServicio::where('vehiculo_id', $record->vehiculo_id)->count();
                        return "Visitas previas: {$visitas}";
                    }),

                \Filament\Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable()
                    ->weight('bold')
                    // Esto agrega la marca y modelo en texto pequeño debajo de las placas
                    ->description(fn (\App\Models\Recordatorio $record) => "{$record->vehiculo->marca} {$record->vehiculo->modelo}")
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->wrap(),

                \Filament\Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'gray' => 'Pendiente',
                        'warning' => 'Contactado',
                        'info' => 'Cita Agendada',
                        'success' => 'Completado',
                        'danger' => 'Cancelado',
                    ]),

                \Filament\Tables\Columns\TextColumn::make('observaciones_seguimiento')
                    ->label('Notas del Seguimiento')
                    ->limit(40)
                    ->color('gray')
                    ->wrap(),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('esta_semana')
                    ->label('Toca Esta Semana')
                    ->query(fn (Builder $query) => $query->whereBetween('fecha_programada', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->toggle(),

                \Filament\Tables\Filters\Filter::make('este_mes')
                    ->label('Toca Este Mes')
                    ->query(fn (Builder $query) => $query->whereMonth('fecha_programada', now()->month))
                    ->toggle(),

                // --- NUEVO: FECHADOR PERSONALIZADO (RANGO DE FECHAS) ---
                \Filament\Tables\Filters\Filter::make('rango_fechas')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')
                            ->label('Desde la fecha:'),
                        \Filament\Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta la fecha:'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_programada', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_programada', '<=', $date),
                            );
                    })
                    // Esto agrega las "etiquetas" (chips) en la parte superior de la tabla indicando el filtro activo
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['desde'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Desde: ' . \Carbon\Carbon::parse($data['desde'])->format('d/m/Y'))
                                ->removeField('desde');
                        }
                        if ($data['hasta'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Hasta: ' . \Carbon\Carbon::parse($data['hasta'])->format('d/m/Y'))
                                ->removeField('hasta');
                        }
                        return $indicators;
                    }),
                // -------------------------------------------------------
            ])
            ->actions([

                \Filament\Tables\Actions\EditAction::make(),

                // 1. EL BOTÓN PARA GESTIONAR EL ESTATUS (Se mantiene igual)
                \Filament\Tables\Actions\Action::make('gestionar')
                    ->label('Gestionar')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('primary')
                    ->modalHeading('Seguimiento con el Cliente')
                    ->form([
                        \Filament\Forms\Components\Select::make('estatus')
                            ->label('Resultado de la comunicación')
                            ->options([
                                'Contactado' => 'Contactado (Aún no decide)',
                                'Cita Agendada' => 'Agendó una Cita',
                                'Cancelado' => 'Cancelado (Ya lo hizo en otro lado / Vendió el auto)',
                            ])
                            ->default(fn (Recordatorio $record) => $record->estatus)
                            ->live()
                            ->required(),

                        \Filament\Forms\Components\DateTimePicker::make('fecha_hora_cita')
                            ->label('Fecha y Hora de la Cita')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('estatus') === 'Cita Agendada')
                            ->required(fn (\Filament\Forms\Get $get) => $get('estatus') === 'Cita Agendada'),

                        \Filament\Forms\Components\Textarea::make('observaciones_seguimiento')
                            ->label('Observaciones (¿Qué dijo el cliente?)')
                            ->placeholder('Ej. Tuvo una urgencia en carretera y lo cambió en otro lado...')
                            ->required(fn (\Filament\Forms\Get $get) => $get('estatus') === 'Cancelado'),
                    ])
                    ->action(function (Recordatorio $record, array $data): void {
                        $record->update($data);
                    }),

                // 2. BOTÓN DE WHATSAPP: INVITAR A AGENDAR (Solo visible si aún no hay cita)
                \Filament\Tables\Actions\Action::make('whatsapp_invitar')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (Recordatorio $record) => in_array($record->estatus, ['Pendiente', 'Contactado']))
                    ->url(function (\App\Models\Recordatorio $record) {
                        $cliente = $record->cliente;
                        $vehiculo = $record->vehiculo;
                        $taller = $record->taller;

                        $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);
                        if (strlen($telefono) == 10) { $telefono = '52' . $telefono; }

                        $nombre = trim($cliente->nombre);
                        $auto = "{$vehiculo->marca} {$vehiculo->modelo}";
                        $servicio = $record->motivo;

                        $nombreTaller = $taller->nombre_comercial ?? 'tu taller de confianza';
                        $domicilio = $taller->domicilio ?? 'nuestras instalaciones';

                        if ($record->nivel_importancia === 'Alta') {
                            $mensaje = "Hola *{$nombre}* 👨‍🔧. Te escribimos de *{$nombreTaller}*. Revisando el expediente de tu *{$auto}*, notamos que ya es tiempo de realizar su *{$servicio}*. Al ser un tema de vital importancia para tu seguridad, queríamos recordarte agendar una revisión. Te esperamos en *{$domicilio}*. ¿Qué día de esta semana te queda mejor?";
                        } elseif ($record->nivel_importancia === 'Baja') {
                            $mensaje = "¡Hola *{$nombre}*! Esperamos que estés teniendo una excelente semana 🚘. Te saludamos de *{$nombreTaller}* para recordarte que a tu *{$auto}* ya le tocaría su *{$servicio}*. Cuando tengas un espacio, avísanos para agendarte y dejarlo al 100% en *{$domicilio}*.";
                        } else {
                            $mensaje = "Hola *{$nombre}* 👨‍🔧. En *{$nombreTaller}* llevamos el control de tu *{$auto}* y el sistema nos indica que ya corresponde realizarle: *{$servicio}*. ¿Te gustaría agendar una cita para estos próximos días y mantenerlo en óptimas condiciones? Estamos ubicados en *{$domicilio}*.";
                        }

                        return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                    })
                    ->openUrlInNewTab(),

                // 3. BOTÓN DE WHATSAPP: RECORDAR CITA (Solo visible si ya tiene cita agendada)
                \Filament\Tables\Actions\Action::make('whatsapp_recordatorio')
                    ->label('Recordar Cita')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->visible(fn (Recordatorio $record) => $record->estatus === 'Cita Agendada')
                    ->url(function (\App\Models\Recordatorio $record) {
                        $cliente = $record->cliente;
                        $taller = $record->taller;

                        $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);
                        if (strlen($telefono) == 10) { $telefono = '52' . $telefono; }

                        $nombre = trim($cliente->nombre);

                        // Validamos que la fecha exista para que no truene el código
                        $fecha = $record->fecha_hora_cita ? ($record->fecha_hora_cita->isToday() ? 'el día de hoy' : 'el ' . $record->fecha_hora_cita->format('d/m/Y')) : 'pronto';
                        $hora = $record->fecha_hora_cita ? $record->fecha_hora_cita->format('h:i A') : 'la hora acordada';

                        $nombreTaller = $taller->nombre_comercial ?? 'tu taller de confianza';
                        $domicilio = $taller->domicilio ?? 'nuestras instalaciones';

                        $mensaje = "¡Hola *{$nombre}*! 👨‍🔧 Te saludamos de *{$nombreTaller}*. Solo pasamos a confirmarte que tenemos todo listo para recibir tu vehículo {$fecha} a las *{$hora}* en *{$domicilio}*. ¡Te esperamos!";

                        return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                    })
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordatorios::route('/'),
            'create' => Pages\CreateRecordatorio::route('/create'),
            'edit' => Pages\EditRecordatorio::route('/{record}/edit'),
        ];
    }
}
