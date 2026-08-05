<?php

namespace App\Filament\Pages;

use App\Models\Taller;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class PerfilTaller extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
//    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Perfil del Taller';
    protected static ?string $title = 'Identidad y Contacto';
    protected static ?string $slug = 'perfil-taller';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.perfil-taller';

    public ?array $data = [];

    // EL CANDADO: Solo los Super Admin pueden entrar a esta pantalla
    public static function canAccess(): bool
    {
        // 1. Definimos los roles permitidos (siempre en minúsculas para la comparación)
        $rolesPermitidos = ['admin taller', 'super_admin', 'admin'];

        // 2. Obtenemos los roles del usuario actual, los convertimos a minúsculas y buscamos coincidencias
        return auth()->user()->roles->pluck('name')
            ->map(fn($rol) => strtolower($rol))
            ->intersect($rolesPermitidos)
            ->isNotEmpty();
    }

    // Carga los datos actuales de tu taller al abrir la página
    public function mount(): void
    {
        $taller = Taller::find(auth()->user()->taller_id);
        if ($taller) {
            $this->form->fill($taller->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identidad Visual y Datos de Contacto')
                    ->description('Estos datos aparecerán públicamente en los reportes PDF, Cotizaciones y mensajes de WhatsApp.')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logotipo Oficial del Taller')
                            ->disk('s3')
                            ->directory('talleres_logos')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->moveFiles() // <--- ESTO ES VITAL: Fuerza a Filament a mover el archivo de tmp al destino
                            ->getUploadedFileNameForStorageUsing(
                                function (\Illuminate\Http\UploadedFile $file) {
                                    $tallerId = auth()->user()->taller_id;
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = time(); // Evita caché en el navegador
                                    return "logo_taller_{$tallerId}_{$timestamp}.{$extension}";
                                }
                            )
                            ->columnSpanFull(),

                        TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial del Taller')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('telefono')
                            ->label('Teléfono Fijo / Oficina')
                            ->tel()
                            ->columnSpan(1),

                        TextInput::make('whatsapp_publico')
                            ->label('WhatsApp de Atención al Cliente')
                            ->tel()
                            ->columnSpan(1),

                        Textarea::make('domicilio')
                            ->label('Dirección Completa (Para facturas y PDFs)')
                            ->rows(3)
                            ->columnSpanFull(),


                        TextInput::make('horario_atencion')
                            ->label('Horarios de Atención (Ej. Lun-Vie 9am-6pm, Sáb 9am-2pm)')
                            ->placeholder('Ej. Lunes a Viernes de 9:00 AM a 6:00 PM')
                            ->columnSpanFull(),

                    ])->columns(4),

                // ... (aquí termina tu Section anterior) ...

                // --- NUEVA SECCIÓN: MEMBRESÍA Y RENOVACIÓN ---
                Section::make('Suscripción y Pagos')
                    ->description('Administra el estado de tu membresía de Autonix.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('suscripcion_info')
                            ->label('')
                            ->content(function () {
                                $taller = auth()->user()->taller;
                                $fecha = $taller->vencimiento_suscripcion ? \Carbon\Carbon::parse($taller->vencimiento_suscripcion)->format('d/m/Y') : 'No registrada';
                                $plan = $taller->plan ?? 'Estándar';

                                $mensaje = urlencode("Hola equipo de Syntaro, quiero renovar mi suscripción de Autonix. Mi taller es: {$taller->nombre_comercial}");
                                $whatsappUrl = "https://wa.me/528148234023?text={$mensaje}";

                                return new HtmlString('
                                    <div style="display: flex; flex-direction: column; padding: 1.5rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.75rem;" class="dark:bg-gray-800/50 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between text-center">
                                        <div style="margin-bottom: 1rem;" class="sm:mb-0">
                                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;" class="dark:text-gray-400">
                                                Plan actual:
                                                <strong style="color: #111827; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-white">🚀 ' . $plan . '</strong>
                                            </p>
                                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;" class="dark:text-gray-400">
                                                Próximo pago:
                                                <strong style="color: #111827;" class="dark:text-white">' . $fecha . '</strong>
                                            </p>
                                        </div>

                                        <!-- Botón de WhatsApp -->
                                        <div>
                                            <a href="' . $whatsappUrl . '" target="_blank"
                                               style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background-color: #25D366; color: white; font-size: 0.875rem; font-weight: bold; border-radius: 0.5rem; text-decoration: none; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); transition: transform 0.2s, background-color 0.2s;"
                                               onmouseover="this.style.backgroundColor=\'#20b858\'; this.style.transform=\'scale(1.05)\'"
                                               onmouseout="this.style.backgroundColor=\'#25D366\'; this.style.transform=\'scale(1)\'">
                                                <svg style="width: 1.25rem; height: 1.25rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.064 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                Renovar
                                            </a>
                                        </div>
                                    </div>
                                ');
                            })
                    ]),



            ])
            ->statePath('data'); // Conecta los campos con el arreglo $data





    }

    // Botón de guardar inferior
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->color('primary'),
        ];
    }

    // Acción que se ejecuta al darle "Guardar Cambios"
    public function save(): void
    {
        // Obtenemos los datos limpios del formulario
        $data = $this->form->getState();
        $taller = Taller::find(auth()->user()->taller_id);

        if ($taller) {
            // Forzamos la actualización manual en la base de datos
            $taller->update([
                'logo_path' => $data['logo_path'] ?? $taller->logo_path,
                'nombre_comercial' => $data['nombre_comercial'] ?? $taller->nombre_comercial,
                'telefono' => $data['telefono'] ?? null,
                'whatsapp_publico' => $data['whatsapp_publico'] ?? null,
                'domicilio' => $data['domicilio'] ?? null,
                'horario_atencion' => $data['horario_atencion'] ?? $taller->horario_atencion,
            ]);

            Notification::make()
                ->success()
                ->title('¡Perfil actualizado!')
                ->body('Tu logotipo y datos ya aparecen en los PDFs y portales de los clientes.')
                ->send();
        }
    }
}
