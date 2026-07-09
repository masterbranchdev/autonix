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

class PerfilTaller extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Perfil del Taller';
    protected static ?string $title = 'Identidad y Contacto';
    protected static ?string $slug = 'perfil-taller';

    protected static string $view = 'filament.pages.perfil-taller';

    public ?array $data = [];

    // EL CANDADO: Solo los Super Admin pueden entrar a esta pantalla
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
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
                    ])->columns(4)
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
            ]);

            Notification::make()
                ->success()
                ->title('¡Perfil actualizado!')
                ->body('Tu logotipo y datos ya aparecen en los PDFs y portales de los clientes.')
                ->send();
        }
    }
}
