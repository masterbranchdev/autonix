<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdenServicioResource\Pages;
use App\Filament\Resources\OrdenServicioResource\RelationManagers;
use App\Models\OrdenServicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdenServicioResource extends Resource
{
    protected static ?string $model = OrdenServicio::class;

    protected static ?string $navigationGroup = 'Operación del Taller';
    protected static ?string $modelLabel = 'Orden de Servicio';
    protected static ?string $pluralModelLabel = 'Órdenes de Servicio';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                // Buscador inteligente de autos
                \Filament\Forms\Components\Select::make('vehiculo_id')
                    ->label('Vehículo a ingresar')
                    ->relationship('vehiculo', 'id') // Usamos el ID internamente para que nunca falle
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Vehiculo $record) => trim("{$record->marca} {$record->modelo} " . ($record->placas ? "- Placas: {$record->placas}" : '')))
                    ->searchable(['marca', 'modelo', 'placas']) // Permite al mecánico buscar por marca o placa
                    ->preload() // ¡El secreto! Carga todos los autos en la lista en cuanto haces clic
                    ->required()
                    ->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('folio')
                    ->label('Folio')
                    ->placeholder('Se generará automáticamente al guardar')
                    ->disabled() // Lo pone en gris para que no puedan escribir
                    ->dehydrated(false), // Evita que Filament intente guardar el campo vacío

                \Filament\Forms\Components\DateTimePicker::make('fecha_ingreso')
                    ->label('Fecha y hora de ingreso')
                    ->default(fn () => now())
                    ->required(),

                // --- INICIO DEL NUEVO SELECTOR DE ESTATUS ---
                \Filament\Forms\Components\Select::make('estatus')
                    ->label('Estatus del Vehículo')
                    ->options([
                        'Ingresado' => 'Ingresado (Paso 1)',
                        'En Revisión' => 'En Revisión (Paso 2)',
                        'Cotizando' => 'Cotizando (Paso 3)',
                        'En Reparación' => 'En Reparación (Paso 4)',
                        'Revisión Final' => 'Revisión Final (Paso 5)',
                        'Listo' => 'Listo para entrega (Paso 6)',
                        'Entregado' => 'Vehículo Entregado (Paso 7)',
                    ])
                    ->default('Ingresado') // Por defecto arranca en el paso 1
                    ->required()
                    ->columnSpanFull(),
                // --- FIN DEL NUEVO SELECTOR ---

                \Filament\Forms\Components\Select::make('nivel_gasolina')
                    ->options([
                        'E' => 'Vacío (E)',
                        '1/4' => '1/4 de Tanque',
                        '1/2' => 'Medio Tanque',
                        '3/4' => '3/4 de Tanque',
                        'F' => 'Lleno (F)',
                    ]),

                \Filament\Forms\Components\Toggle::make('ingreso_grua')
                    ->label('¿Ingresó en grúa?')
                    ->inline(false),

                // El Checklist interactivo (Magia visual de Filament)
                \Filament\Forms\Components\CheckboxList::make('inventario')
                    ->label('Inventario del Vehículo')
                    ->options([
                        'gato' => 'Gato',
                        'herramientas' => 'Herramientas',
                        'triangulos' => 'Triángulos',
                        'tapetes' => 'Tapetes',
                        'llanta_refaccion' => 'Llanta refacción',
                        'extintor' => 'Extintor',
                        'antena' => 'Antena',
                        'emblemas' => 'Emblemas',
                        'estereo' => 'Estéreo',
                        'encendedor' => 'Encendedor',
                    ])
                    ->columns(3) // Lo divide en 3 columnas para que parezca el PDF
                    ->columnSpanFull(),

                \Filament\Forms\Components\Textarea::make('trabajo_a_realizar')
                    ->columnSpanFull(),

                \Filament\Forms\Components\Textarea::make('observaciones')
                    ->columnSpanFull(),

                // TESTIGOS DEL TABLERO (Con Íconos y Botones interactivos)
                \Filament\Forms\Components\ToggleButtons::make('testigos')
                    ->label('Testigos encendidos en el tablero')
                    ->multiple() // Permite seleccionar más de uno, actuando como Checkbox
                    ->options([
                        'check_engine' => 'Check Engine',
                        'abs' => 'ABS',
                        'aceite' => 'Presión de Aceite',
                        'bateria' => 'Batería',
                        'temperatura' => 'Temperatura',
                        'freno_mano' => 'Freno (P)',
                        'bolsas_aire' => 'Bolsas de Aire',
                        'llantas' => 'Llantas',
                        'luces_altas' => 'Luces',
                        'traccion' => 'Tracción',
                    ])
                    ->icons([
                        'check_engine' => 'heroicon-o-cog-8-tooth',
                        'abs' => 'heroicon-o-exclamation-circle',
                        'aceite' => 'heroicon-o-beaker',
                        'bateria' => 'heroicon-o-bolt', // Usamos el rayo de energía
                        'temperatura' => 'heroicon-o-fire',
                        'freno_mano' => 'heroicon-o-stop-circle', // Ícono de alto en círculo
                        'bolsas_aire' => 'heroicon-o-shield-exclamation', // Escudo de alerta
                        'llantas' => 'heroicon-o-lifebuoy', // Este ícono es idéntico a una llanta
                        'luces_altas' => 'heroicon-o-light-bulb', // Este ícono es idéntico a una llanta
                        'traccion' => 'heroicon-o-wrench-screwdriver', // Este ícono es idéntico a una llanta
                    ])
                    ->columns(4) // Lo acomoda en una cuadrícula bonita
                    ->columnSpanFull(),

// DAÑOS PREEXISTENTES (Hack Maestro envolviendo con Groups)
                \Filament\Forms\Components\Section::make('Daños preexistentes del vehículo (Dibujar zonas afectadas)')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('estilos_css')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    /* 1. Destruimos por fuerza bruta las cajas blancas internas */
                                    .envoltura-lienzo .fi-input-wrp,
                                    .envoltura-lienzo [x-data],
                                    .envoltura-lienzo canvas {
                                        background-color: transparent !important;
                                        background: transparent !important;
                                        box-shadow: none !important;
                                    }

                                    /* 2. Aumentamos la altura de la caja de dibujo a 200px para mayor comodidad */
                                    .envoltura-lienzo canvas {
                                        border: 2px dashed #d1d5db !important;
                                        border-radius: 8px !important;
                                        height: 200px !important; /* <--- Lienzo más alto */
                                    }

                                    /* 3. Cambiamos "contain" por "90% auto" para hacer un efecto Zoom sobre el auto */
                                    .fondo-derecho canvas { background-image: url("' . asset('img/auto-derecho.jpg') . '") !important; background-size: 90% auto !important; background-repeat: no-repeat !important; background-position: center !important; }
                                    .fondo-frente canvas { background-image: url("' . asset('img/auto-frente.jpg') . '") !important; background-size: 60% auto !important; background-repeat: no-repeat !important; background-position: center !important; }
                                    .fondo-detras canvas { background-image: url("' . asset('img/auto-detras.jpg') . '") !important; background-size: 60% auto !important; background-repeat: no-repeat !important; background-position: center !important; }
                                    .fondo-izquierdo canvas { background-image: url("' . asset('img/auto-izquierdo.jpg') . '") !important; background-size: 90% auto !important; background-repeat: no-repeat !important; background-position: center !important; }
                                </style>
                            '))
                            ->columnSpanFull(),

                        // ENVOLTURAS MAESTRAS: Group asegura que la clase CSS sí se aplique en el HTML
                        \Filament\Forms\Components\Group::make()
                            ->extraAttributes(['class' => 'envoltura-lienzo fondo-derecho'])
                            ->schema([
                                \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('danios_carroceria.lado_derecho')
                                    ->label('Lado Derecho')
                                    ->backgroundColor('rgba(0,0,0,0)')
                                    ->backgroundColorOnDark('rgba(0,0,0,0)')
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->penColor('#ff0000') // Tinta Roja
                                    ->clearable(),
                            ]),

                        \Filament\Forms\Components\Group::make()
                            ->extraAttributes(['class' => 'envoltura-lienzo fondo-frente'])
                            ->schema([
                                \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('danios_carroceria.frente')
                                    ->label('Frente')
                                    ->backgroundColor('rgba(0,0,0,0)')
                                    ->backgroundColorOnDark('rgba(0,0,0,0)')
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->penColor('#ff0000')
                                    ->clearable(),
                            ]),

                        \Filament\Forms\Components\Group::make()
                            ->extraAttributes(['class' => 'envoltura-lienzo fondo-detras'])
                            ->schema([
                                \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('danios_carroceria.detras')
                                    ->label('Detrás')
                                    ->backgroundColor('rgba(0,0,0,0)')
                                    ->backgroundColorOnDark('rgba(0,0,0,0)')
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->penColor('#ff0000')
                                    ->clearable(),
                            ]),

                        \Filament\Forms\Components\Group::make()
                            ->extraAttributes(['class' => 'envoltura-lienzo fondo-izquierdo'])
                            ->schema([
                                \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('danios_carroceria.lado_izquierdo')
                                    ->label('Lado Izquierdo')
                                    ->backgroundColor('rgba(0,0,0,0)')
                                    ->backgroundColorOnDark('rgba(0,0,0,0)')
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->penColor('#ff0000')
                                    ->clearable(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                    // FIRMA DIGITAL Y NOMBRE DE QUIEN ENTREGA
                    \Filament\Forms\Components\Section::make('Firma de Conformidad')
                    ->description('Persona que entrega el vehículo en el taller')
                    ->schema([
                        \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('firma')
                            ->label('Firma digital')
                            ->dotSize(2.0)
                            ->lineMinWidth(1.0)
                            ->lineMaxWidth(2.5)
                            ->clearable()
                            ->columnSpanFull(),

                        \Filament\Forms\Components\TextInput::make('persona_que_entrega')
                            ->label('Nombre completo de quien firma')
                            ->placeholder('Ej. Juan Pérez - Chofer')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('folio')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('vehiculo.placas')
                    ->label('Vehículo')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('fecha_ingreso')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
                \Filament\Tables\Columns\BadgeColumn::make('estatus')
                    ->colors([
                        'gray' => 'Ingresado',
                        'warning' => 'En Revisión',
                        'info' => 'Cotizando',
                        'danger' => 'En Reparación',
                        'primary' => 'Revisión Final',
                        'success' => fn ($state) => in_array($state, ['Listo', 'Entregado']),
                    ])
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),

                // --- NUEVO: BOTÓN DE CAMBIO RÁPIDO DE ESTATUS ---
                \Filament\Tables\Actions\Action::make('cambiar_estatus')
                    ->label('Estatus')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Actualizar Estatus del Vehículo')
                    ->modalWidth('sm')
                    ->form([
                        \Filament\Forms\Components\Select::make('estatus')
                            ->hiddenLabel()
                            ->options([
                                'Ingresado' => 'Ingresado (Paso 1)',
                                'En Revisión' => 'En Revisión (Paso 2)',
                                'Cotizando' => 'Cotizando (Paso 3)',
                                'En Reparación' => 'En Reparación (Paso 4)',
                                'Revisión Final' => 'Revisión Final (Paso 5)',
                                'Listo' => 'Listo para entrega (Paso 6)',
                                'Entregado' => 'Vehículo Entregado (Paso 7)',
                            ])
                            ->default(fn (\App\Models\OrdenServicio $record) => $record->estatus)
                            ->required(),
                    ])
                    ->action(function (\App\Models\OrdenServicio $record, array $data): void {
                        $record->update(['estatus' => $data['estatus']]);

                        \Filament\Notifications\Notification::make()
                            ->title('Estatus actualizado a: ' . $data['estatus'])
                            ->success()
                            ->send();
                    }),
                // --- FIN DEL NUEVO BOTÓN ---


                // Botón de imprimir
                \Filament\Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (\App\Models\OrdenServicio $record) => route('orden.imprimir', $record))
                    ->openUrlInNewTab(),

                // EL BOTÓN MÁGICO DE WHATSAPP PARA LA RECEPCIÓN
                \Filament\Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (\App\Models\OrdenServicio $record) {
                        $vehiculo = $record->vehiculo;
                        $cliente = $vehiculo->cliente;
                        $taller = $record->taller;

                        $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);
                        if (strlen($telefono) == 10) { $telefono = '52' . $telefono; }

                        $link = route('portal.status', $record->token_url);
                        $nombreTaller = $taller ? $taller->nombre_comercial : 'Autonix';

                        // Mensaje de bienvenida inicial
                        $mensaje = "Hola *{$cliente->nombre}*, bienvenido a *{$nombreTaller}* 👨‍🔧.\n\nHemos recibido tu *{$vehiculo->marca} {$vehiculo->modelo}*.\n\nEn este enlace único podrás ver el *Tracker en tiempo real* de tu servicio, tus inspecciones, cotizaciones y el historial completo de tu auto:\n👉 {$link}\n\nTe notificaremos por aquí cuando haya actualizaciones.";

                        return 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . urlencode($mensaje);
                    })
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdenServicios::route('/'),
            'create' => Pages\CreateOrdenServicio::route('/create'),
            'edit' => Pages\EditOrdenServicio::route('/{record}/edit'),
        ];
    }
}
