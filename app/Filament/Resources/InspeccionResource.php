<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InspeccionResource\Pages;
use App\Filament\Resources\InspeccionResource\RelationManagers;
use App\Models\Inspeccion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InspeccionResource extends Resource
{
    protected static ?string $model = Inspeccion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // 1. Esto cambia el nombre en el menú lateral
    protected static ?string $navigationLabel = 'Inspecciones';

    // 2. Esto cambia el título de la página y las migas de pan (breadcrumbs)
    protected static ?string $pluralModelLabel = 'Inspecciones';

    // 3. (Opcional) Esto cambia el nombre en singular
    protected static ?string $modelLabel = 'Inspección';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('taller_id')
                    ->default(auth()->user()->taller_id),

                // ENCABEZADO
                \Filament\Forms\Components\Section::make('Datos de la Orden')
                    ->schema([
                        \Filament\Forms\Components\Select::make('orden_servicio_id')
                            ->label('Folio de Orden de Servicio')
                            ->relationship(
                                name: 'ordenServicio',
                                titleAttribute: 'folio',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with('vehiculo.cliente')
                            )
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\OrdenServicio $record) => "Folio: {$record->folio} - " . ($record->vehiculo ? $record->vehiculo->placas : 'Sin placas'))
                            ->searchable(['folio'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // TABS DE REVISIÓN
                \Filament\Forms\Components\Tabs::make('Inspeccion Completa')
                    ->tabs([

                        // TAB 1: PUNTOS DE REVISIÓN (Los 19 puntos exactos)
                        \Filament\Forms\Components\Tabs\Tab::make('Puntos de Revisión')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                \Filament\Forms\Components\Fieldset::make('Estado Visual y Fluidos (Verde = OK, Amarillo = Futuro, Rojo = Urgente)')
                                    ->schema([
                                        self::makeSemaforo('lavaparabrisas', 'Fluido lavaparabrisas'),
                                        self::makeSemaforo('transmision', 'Fluido de transmisión automática'),
                                        self::makeSemaforo('frenos', 'Líquido de frenos'),
                                        self::makeSemaforo('direccion', 'Líquido de dirección hidráulica'),
                                        self::makeSemaforo('anticongelante', 'Anticongelante'),
                                        self::makeSemaforo('transeje_embrague', 'Transeje / Caja transf. / Fluido embrague'),
                                        self::makeSemaforo('parabrisas', 'Cuarteaduras en el parabrisas'),
                                        self::makeSemaforo('luces_claxon', 'Claxon y luces int/ext'),
                                        self::makeSemaforo('aspersor_limpiadores', 'Aspersor lavaparabrisas y limpiadores'),
                                        self::makeSemaforo('enfriamiento', 'Sistema de enfriamiento (Fugas/daños)'),
                                        self::makeSemaforo('fugas_aceite', 'Fugas de aceite y/o fluidos'),
                                        self::makeSemaforo('cubrepolvos', 'Cubrepolvos de las flechas'),
                                        self::makeSemaforo('escape', 'Sistema de escape (fugas, daños)'),
                                        self::makeSemaforo('bandas', 'Bandas'),
                                        self::makeSemaforo('direccion_articulaciones', 'Dirección, articulaciones y baleros'),
                                        self::makeSemaforo('suspension', 'Suspensión y amortiguadores'),
                                        self::makeSemaforo('lineas_frenos', 'Líneas y mangueras de frenos'),
                                        self::makeSemaforo('terminales_bateria', 'Terminales del acumulador'),
                                        self::makeSemaforo('embrague', 'Funcionamiento del embrague'),
                                    ])->columns(2),

                                \Filament\Forms\Components\Textarea::make('observaciones_puntos')
                                    ->label('Observaciones generales de la revisión')
                                    ->columnSpanFull(),
                            ]),

                        // TAB 2: LLANTAS Y BALATAS
                        \Filament\Forms\Components\Tabs\Tab::make('Llantas y Balatas')
                            ->icon('heroicon-o-lifebuoy')
                            ->schema([
                                \Filament\Forms\Components\Fieldset::make('Presión y Vida de Llantas')
                                    ->schema([
                                        self::makeLlantaForm('di', 'Delantera Izquierda'),
                                        self::makeLlantaForm('dd', 'Delantera Derecha'),
                                        self::makeLlantaForm('ti', 'Trasera Izquierda'),
                                        self::makeLlantaForm('td', 'Trasera Derecha'),
                                    ])->columns(2),

                                \Filament\Forms\Components\Fieldset::make('Grosor de Balatas (Frenos)')
                                    ->schema([
                                        self::makeBalataForm('di', 'Delantera Izquierda'),
                                        self::makeBalataForm('dd', 'Delantera Derecha'),
                                        self::makeBalataForm('ti', 'Trasera Izquierda'),
                                        self::makeBalataForm('td', 'Trasera Derecha'),
                                    ])->columns(2),
                            ]),

                        // TAB 3: ADICIONALES Y BATERÍA
                        \Filament\Forms\Components\Tabs\Tab::make('Adicionales y Batería')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                \Filament\Forms\Components\Section::make('Estado del Acumulador (Batería)')
                                    ->schema([
                                        \Filament\Forms\Components\ToggleButtons::make('bateria.estado')
                                            ->label('Estado Visual')
                                            ->options(['bien' => 'Bien', 'mal' => 'Mal'])
                                            ->colors(['bien' => 'success', 'mal' => 'danger'])
                                            ->inline(),
                                        \Filament\Forms\Components\TextInput::make('bateria.amperaje')
                                            ->label('Amperaje en frío (CCA)')
                                            ->numeric()->suffix('CCA'),
                                    ])->columns(2),

                                \Filament\Forms\Components\Section::make('Servicios Adicionales Recomendados')
                                    ->schema([
                                        \Filament\Forms\Components\Grid::make(4)->schema([
                                            \Filament\Forms\Components\Toggle::make('adicionales.rotacion')->label('Rotación de llantas')->inline(false),
                                            \Filament\Forms\Components\Toggle::make('adicionales.filtro_aire')->label('Filtro de aire')->inline(false),
                                            \Filament\Forms\Components\Toggle::make('adicionales.plumas')->label('Plumas limpiadores')->inline(false),
                                            \Filament\Forms\Components\Toggle::make('adicionales.reparacion_llanta')->label('Reparación de llanta')->inline(false),
                                            \Filament\Forms\Components\Toggle::make('adicionales.frenos')->label('Revisión Frenos')->inline(false),
                                        ]),

                                        // Campos para especificar detalles
                                        \Filament\Forms\Components\Grid::make(3)->schema([
                                            \Filament\Forms\Components\TextInput::make('adicionales.fugas_especificar')->label('Especificar Fugas de aceite/fluidos (Tab 1)'),
                                            \Filament\Forms\Components\TextInput::make('adicionales.frenos_especificar')->label('Especificar tipo de frenos a reparar'),
                                            \Filament\Forms\Components\TextInput::make('adicionales.otros')->label('Otros servicios recomendados'),
                                        ]),
                                    ]),
                            ]),
                    ])->columnSpanFull(),

                // FIRMA DEL CLIENTE
                \Filament\Forms\Components\Section::make('Firma de Conformidad')
                    ->schema([
                        \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('firma')
                            ->label('Firma del Cliente')
                            ->dotSize(2.0)
                            ->lineMinWidth(1.0)
                            ->lineMaxWidth(2.5)
                            ->clearable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // --- FUNCIONES AUXILIARES PARA NO REPETIR CÓDIGO ---
    private static function makeSemaforo($key, $label)
    {
        return \Filament\Forms\Components\ToggleButtons::make("check_puntos.{$key}")
            ->label($label)
            ->options(['verde' => 'OK', 'amarillo' => 'Atención Futura', 'rojo' => 'Urgente'])
            ->colors(['verde' => 'success', 'amarillo' => 'warning', 'rojo' => 'danger'])
            ->inline();
    }

    private static function makeLlantaForm($key, $label)
    {
        return \Filament\Forms\Components\Grid::make(3)
            ->schema([
                \Filament\Forms\Components\Placeholder::make("lbl_{$key}")->content($label)->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make("llantas.{$key}.psi")->label('PSI')->numeric(),
                \Filament\Forms\Components\TextInput::make("llantas.{$key}.mm")->label('Profundidad (mm)')->numeric(),
                \Filament\Forms\Components\Select::make("llantas.{$key}.estado")->label('Estado')
                    ->options(['verde' => 'OK (>50%)', 'amarillo' => 'Regular (20-50%)', 'rojo' => 'Reemplazar (<20%)']),
            ])->extraAttributes(['class' => 'border p-4 rounded-lg shadow-sm']);
    }

    private static function makeBalataForm($key, $label)
    {
        return \Filament\Forms\Components\Grid::make(2)
            ->schema([
                \Filament\Forms\Components\Placeholder::make("lbl_b_{$key}")->content($label)->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make("balatas.{$key}.mm")->label('Grosor (mm)')->numeric(),
                \Filament\Forms\Components\Select::make("balatas.{$key}.estado")->label('Estado')
                    ->options(['verde' => 'OK (>50%)', 'amarillo' => 'Regular (20-50%)', 'rojo' => 'Reemplazar (<20%)']),
            ])->extraAttributes(['class' => 'border p-4 rounded-lg shadow-sm bg-gray-50']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.folio')->label('Folio O.S.')->searchable()->weight('bold'),
                \Filament\Tables\Columns\TextColumn::make('ordenServicio.vehiculo.placas')->label('Placas')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Fecha Diagnóstico')->dateTime('d/m/Y'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\Action::make('imprimir')
                    ->label('Reporte PDF')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('danger')
                    ->url(fn (\App\Models\Inspeccion $record) => route('inspeccion.imprimir', $record))
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
            'index' => Pages\ListInspeccions::route('/'),
            'create' => Pages\CreateInspeccion::route('/create'),
            'edit' => Pages\EditInspeccion::route('/{record}/edit'),
        ];
    }
}
