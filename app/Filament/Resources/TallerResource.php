<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TallerResource\Pages;
use App\Models\Taller;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TallerResource extends Resource
{
    protected static ?string $model = Taller::class;

    // --- DISEÑO SAAS DEL MENÚ ---
    protected static ?string $navigationGroup = '🚀 Autonix SaaS';
    protected static ?string $navigationLabel = 'Gestión de Talleres';
    protected static ?string $modelLabel = 'Taller Cliente';
    protected static ?string $pluralModelLabel = 'Talleres Clientes';
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    // CANDADO MAESTRO: Solo el super administrador de Autonix puede ver esto
    public static function canAccess(): bool
    {
        // Cambia esto por tu correo real o el rol que uses para ti mismo
        return auth()->user()->email === 'admin@autonix.com.mx';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCIÓN 1: DATOS DEL TALLER
                Forms\Components\Section::make('Información Comercial')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_comercial')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('telefono')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('whatsapp_publico')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('domicilio')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('horario_atencion')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('logo_path')
                            ->label('Ruta del Logo')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(4),

                // SECCIÓN 2: CONTROL SAAS (Suscripciones)
                Forms\Components\Section::make('Control de Suscripción (SaaS)')
                    ->description('Administra los accesos y cortes de servicio de tu cliente.')
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label('Plan Contratado')
                            ->options([
                                'prueba' => '🟢 En Periodo de Prueba (Demo)',
                                'basico' => '🔵 Básico',
                                'pro' => '🟣 Pro',
                                'premium' => '⭐ Premium',
                            ])
                            ->default('prueba')
                            ->required(),

                        Forms\Components\DateTimePicker::make('fecha_suscripcion')
                            ->label('Fecha de Alta')
                            ->required(),

                        Forms\Components\DatePicker::make('vencimiento_suscripcion')
                            ->label('Próximo Corte / Fin de Suscripción'),

                        // EL BOTÓN DEL PÁNICO (Tu campo 'activo')
                        Forms\Components\Toggle::make('activo')
                            ->label('Taller Activo (Permitir acceso al sistema)')
                            ->onColor('success')
                            ->offColor('danger') // Si lo apagas, se pone rojo
                            ->inline(false)
                            ->required(),
                    ])->columns(2),

                // SECCIÓN 3: CONFIGURACIÓN DE API (Twilio)
                Forms\Components\Section::make('Configuración de Mensajería (Twilio API)')
                    ->description('Credenciales exclusivas para el envío de WhatsApp de este taller.')
                    ->collapsed() // Lo mantenemos cerrado por defecto para no saturar la pantalla
                    ->schema([
                        Forms\Components\TextInput::make('twilio_sid')
                            ->label('Account SID')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twilio_token')
                            ->label('Auth Token')
                            ->password() // Oculta el token por seguridad
                            ->revealable()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twilio_whatsapp')
                            ->label('Número de WhatsApp (Twilio)')
                            ->placeholder('Ej. +14155238886')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twilio_tpl_estatus')
                            ->label('Template ID: Estatus')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twilio_tpl_cotizacion')
                            ->label('Template ID: Cotización')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twilio_tpl_recordatorio')
                            ->label('Template ID: Recordatorio')
                            ->maxLength(255),
                    ])->columns(3),

                // --- NUEVA SECCIÓN: INTELIGENCIA ARTIFICIAL ---
                Forms\Components\Section::make('Copiloto Inteligencia Artificial')
                    ->description('Asigna una API Key independiente para controlar los costos de OpenAI por taller.')
                    ->collapsed() // Cerrada por defecto para mantener limpio el panel
                    ->schema([
                        Forms\Components\TextInput::make('openai_api_key')
                            ->label('API Key de OpenAI')
                            ->password() // Oculta la llave con asteriscos
                            ->revealable()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\TextInput::make('limite_ia_mensual')
                    ->label('Límite de Consultas Mensuales')
                    ->numeric()
                    ->default(100)
                    ->required(),

                Forms\Components\TextInput::make('consumo_ia_mes')
                    ->label('Consumo Actual del Mes')
                    ->numeric()
                    ->default(0)
                    ->disabled() // Solo lectura para ti, el sistema lo actualiza solo
                    ->helperText('Se reinicia a 0 cada mes.'),

                // --- NUEVA SECCIÓN: CONFIGURACIÓN DE FACTURAPI ---
                \Filament\Forms\Components\Section::make('Configuración de Facturación (CFDI 4.0)')
                    ->description('Ingresa tus credenciales de Facturapi para habilitar el timbrado de facturas desde Autonix.')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('facturapi_key_test')
                            ->label('API Key (Modo Pruebas / Test)')
                            ->password() // Ocultamos la llave por seguridad
                            ->revealable()
                            ->columnSpan(1),

                        \Filament\Forms\Components\TextInput::make('facturapi_key_live')
                            ->label('API Key (Modo Producción / Live)')
                            ->password()
                            ->revealable()
                            ->columnSpan(1),

                        \Filament\Forms\Components\Toggle::make('facturacion_produccion')
                            ->label('Habilitar Modo Producción (Facturas Reales)')
                            ->helperText('¡Atención! Al activar esto, las facturas tendrán validez fiscal ante el SAT.')
                            ->onColor('danger') // Color rojo para advertir que es en serio
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_comercial')
                    ->label('Taller')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan')
                    ->colors([
                        'success' => 'prueba',
                        'primary' => 'basico',
                        'warning' => 'pro',
                        'danger' => 'premium',
                    ])
                    ->searchable(),

                Tables\Columns\TextColumn::make('vencimiento_suscripcion')
                    ->label('Vencimiento')
                    ->date('d M Y')
                    ->sortable()
                    // Magia: Le pone un texto rojo debajo si ya se venció
                    ->description(fn (Taller $record) => $record->vencimiento_suscripcion && \Carbon\Carbon::parse($record->vencimiento_suscripcion)->isPast() ? '¡VENCIDO!' : ''),

                // EL INDICADOR DEL KILL SWITCH
                Tables\Columns\IconColumn::make('activo')
                    ->label('Acceso')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-lock-closed') // Si no está activo, muestra un candado
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('telefono')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado de Acceso')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Suspendidos / Bloqueados'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTallers::route('/'),
            'create' => Pages\CreateTaller::route('/create'),
            'edit' => Pages\EditTaller::route('/{record}/edit'),
        ];
    }
}
