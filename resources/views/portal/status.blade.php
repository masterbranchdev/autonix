<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Expediente Digital - {{ $orden->vehiculo->placas }}</title>
    <link rel="icon" href="{{ asset('img/autonix_logo_solo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .tracker-step { transition: all 0.3s ease; }
        .tracker-active { background-color: #CC7B2E; color: white; border-color: #CC7B2E; }
        .tracker-done { background-color: #6b7c8d; color: white; border-color: #6b7c8d; }
        .tracker-pending { background-color: #f3f4f6; color: #9ca3af; border-color: #d1d5db; }
        .line-done { background-color: #10b981; }
        .line-pending { background-color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased pb-10">

@php
    $taller = $orden->taller;
    $vehiculo = $orden->vehiculo;
    $cliente = $vehiculo->cliente;

    // Mapeo del estatus (texto libre en Filament) al paso del Tracker
    $estatusOriginal = trim((string) $orden->estatus);
    $estatusActual = \Illuminate\Support\Str::lower($estatusOriginal);

    $pasos = [
        ['id' => 'ingresado', 'label' => 'Ingresado', 'icon' => '📋'],
        ['id' => 'diagnosticando', 'label' => 'Revisión', 'icon' => '🔍'],
        ['id' => 'cotizando', 'label' => 'Cotizando', 'icon' => '💲'],
        ['id' => 'reparacion', 'label' => 'En Taller', 'icon' => '🔧'],
        ['id' => 'calidad', 'label' => 'Revisión Final', 'icon' => '⚙️'],
        ['id' => 'listo', 'label' => 'Listo', 'icon' => '🏁'],
        ['id' => 'entregado', 'label' => 'Entregado', 'icon' => '✅'],
    ];

    // 1) Match exacto contra los valores oficiales del Select de Filament
    $mapaEstatusOficial = [
        'ingresado' => 1,
        'en revisión' => 2,
        'en revision' => 2,
        'cotizando' => 3,
        'en reparación' => 4,
        'en reparacion' => 4,
        'revisión final' => 5,
        'revision final' => 5,
        'listo' => 6,
        'entregado' => 7,
    ];
    $pasoActual = $mapaEstatusOficial[$estatusActual] ?? null;

    // 2) Fallback tolerante por palabras clave (captura libre / variaciones de redacción)
    if (is_null($pasoActual)) {
        if (str_contains($estatusActual, 'entregado')) $pasoActual = 7;
        elseif (str_contains($estatusActual, 'terminado') || str_contains($estatusActual, 'listo')) $pasoActual = 6;
        elseif (str_contains($estatusActual, 'calidad') || str_contains($estatusActual, 'final')) $pasoActual = 5;
        elseif (str_contains($estatusActual, 'reparacion') || str_contains($estatusActual, 'repara')) $pasoActual = 4;
        elseif (str_contains($estatusActual, 'espera') || str_contains($estatusActual, 'cotiz')) $pasoActual = 3;
        elseif (str_contains($estatusActual, 'revis') || str_contains($estatusActual, 'diagnost')) $pasoActual = 2;
        elseif (str_contains($estatusActual, 'ingres')) $pasoActual = 1;
    }

    // 3) Si de plano no reconocemos el estatus, no aparentamos "recién ingresado":
    //    dejamos el tracker en un estado neutro (ningún paso marcado) para no
    //    mostrarle al cliente información incorrecta.
    $estatusNoReconocido = is_null($pasoActual);
    if ($estatusNoReconocido) {
        \Illuminate\Support\Facades\Log::warning("Portal cliente: estatus de orden #{$orden->id} no reconocido por el tracker: '{$estatusOriginal}'");
        $pasoActual = 1;
    }

    $mostrarDocumentos = $pasoActual < 7;

    // Botón de contacto por WhatsApp del taller
    $whatsappUrl = null;
    $telefonoTaller = $taller->whatsapp_publico ?? $taller->telefono ?? null;
    if ($telefonoTaller) {
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefonoTaller);
        if (strlen($telefonoLimpio) == 10) {
            $telefonoLimpio = '52' . $telefonoLimpio;
        }
        if ($telefonoLimpio) {
            $nombreTaller = $taller->nombre_comercial ?? 'Autonix';
            $mensajeWhatsapp = "Hola, tengo una duda sobre mi vehículo {$vehiculo->marca} {$vehiculo->modelo} (folio {$orden->folio}) en {$nombreTaller}.";
            $whatsappUrl = 'https://api.whatsapp.com/send?phone=' . $telefonoLimpio . '&text=' . urlencode($mensajeWhatsapp);
        }
    }
@endphp

<header class="bg-slate-700 text-white p-6 shadow-md rounded-b-3xl">
    <div class="max-w-md mx-auto text-center">
        @if($taller && $taller->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($taller->logo_path) }}" alt="{{ $taller->nombre_comercial ?? 'Autonix' }}" class="mx-auto h-16 mb-2">
        @else
            <h1 class="text-2xl font-black tracking-widest uppercase">{{ $taller->nombre_comercial ?? 'Autonix' }}</h1>
        @endif
        <p class="text-xs text-white mt-2 uppercase tracking-widest">Expediente Digital del Vehículo</p>
    </div>
</header>

@if($whatsappUrl)
    <div class="max-w-lg mx-auto px-4 -mt-3 relative z-10">
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-2xl text-sm font-black uppercase tracking-wider shadow-lg shadow-emerald-500/30 transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 1.67c4.55 0 8.24 3.69 8.24 8.24s-3.69 8.24-8.24 8.24a8.19 8.19 0 01-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 01-1.26-4.38c0-4.55 3.7-8.24 8.24-8.24zm-4.36 4.7c-.16 0-.42.06-.65.31s-.85.83-.85 2.02.87 2.35 1 2.51c.12.16 1.7 2.72 4.19 3.71 2.07.82 2.49.66 2.94.62.45-.04 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.45-.28-.24-.12-1.44-.71-1.66-.79-.22-.08-.39-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
            ¿Dudas? Escríbenos por WhatsApp
        </a>
    </div>
@endif

<main class="max-w-lg mx-auto p-4 space-y-6 mt-2">

    <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-center font-bold text-gray-800 mb-6 uppercase tracking-wider text-sm">Rastreo en vivo</h2>

        <div class="relative flex justify-between items-center w-full">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 z-0 rounded-full"></div>
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-1 bg-slate-500 z-0 rounded-full transition-all duration-500" style="width: {{ $estatusNoReconocido ? 0 : ((($pasoActual - 1) / (count($pasos) - 1)) * 100) }}%"></div>

            @foreach($pasos as $index => $paso)
                @php
                    $numeroPaso = $index + 1;
                    if($estatusNoReconocido) { $clase = 'tracker-pending'; }
                    elseif($numeroPaso < $pasoActual) { $clase = 'tracker-done'; }
                    elseif($numeroPaso == $pasoActual) { $clase = 'tracker-active shadow-lg shadow-orange-200 scale-110'; }
                    else { $clase = 'tracker-pending'; }
                @endphp

                <div class="relative z-10 flex flex-col items-center group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg border-2 {{ $clase }}">
                        {{ $paso['icon'] }}
                    </div>
                    <span class="text-[8px] sm:text-[10px] leading-tight font-bold mt-2 text-center absolute -bottom-7 sm:-bottom-5 w-12 sm:w-20
                            {{ !$estatusNoReconocido && $numeroPaso == $pasoActual ? 'text-orange-600' : 'text-gray-400' }}">
                            {{ $paso['label'] }}
                        </span>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <p class="text-2xl font-black text-slate-800 break-words">{{ mb_strtoupper($estatusOriginal) }}</p>
            <p class="text-sm text-gray-500 mt-1">Última actualización: {{ $orden->updated_at->diffForHumans() }}</p>
            @if($estatusNoReconocido)
                <p class="text-xs text-amber-600 font-bold mt-2">⏳ Estamos actualizando el estatus de tu vehículo.</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-5 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400 font-bold uppercase">Unidad</p>
            <p class="text-lg font-black text-slate-800">{{ trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? '')) ?: 'Vehículo sin datos registrados' }}</p>
            <p class="text-sm text-gray-500">{{ $vehiculo->placas }}@if($vehiculo->color) • {{ $vehiculo->color }} @endif</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 font-bold uppercase">Ingreso</p>
            <p class="text-sm font-bold text-slate-800">{{ $orden->created_at->format('d M, Y') }}</p>
            <p class="text-xs text-gray-500">Folio: {{ $orden->folio }}</p>
        </div>
    </div>

    @if($mostrarDocumentos)
    <div class="bg-white rounded-3xl shadow-sm p-5 border border-gray-100">
        <h3 class="text-sm font-black uppercase text-slate-700 tracking-wider mb-3">Documentos</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('portal.orden.pdf', $orden->token_url) }}" target="_blank" class="flex flex-col items-center justify-center gap-1 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-blue-300 text-slate-700 hover:text-blue-700 py-3 px-2 rounded-2xl text-xs font-bold transition-all">
                <span class="text-xl">📄</span>
                Orden de Servicio
            </a>
            @if($orden->inspecciones->count() > 0)
            <a href="{{ route('portal.inspeccion.pdf', $orden->token_url) }}" target="_blank" class="flex flex-col items-center justify-center gap-1 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-blue-300 text-slate-700 hover:text-blue-700 py-3 px-2 rounded-2xl text-xs font-bold transition-all">
                <span class="text-xl">📋</span>
                Hoja de Inspección
            </a>
            @endif
        </div>
    </div>
    @endif

    @if($pasoActual >= 3 && $mostrarDocumentos && $orden->cotizaciones->count() > 0)
        <div class="bg-white rounded-3xl shadow-sm p-1 border border-blue-100 mt-6">
            <div class="bg-blue-50 rounded-[22px] p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-black uppercase text-blue-900 tracking-wider">Cotizaciones Actuales</h3>
                    <span class="bg-blue-200 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ $orden->cotizaciones->count() }}</span>
                </div>

                @foreach($orden->cotizaciones as $cotizacion)
                    <div class="bg-white rounded-2xl p-4 shadow-sm mb-3 last:mb-0 border border-blue-100">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-bold text-slate-700">Folio: {{ $cotizacion->folio }}</span>
                            <span class="text-[10px] px-2 py-1 rounded-full font-black uppercase tracking-wider
                            @if($cotizacion->estatus == 'Aprobada') bg-emerald-100 text-emerald-700
                            @else bg-amber-100 text-amber-700 @endif">
                            {{ $cotizacion->estatus }}
                        </span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-500 font-bold">Total a pagar:</span>
                            <span class="text-2xl font-black text-emerald-600">${{ number_format($cotizacion->total, 2) }}</span>
                        </div>

                        <div class="mt-4 border-t border-blue-50 pt-3">
                            <a href="{{ route('portal.cotizacion.pdf', ['token' => $orden->token_url, 'id' => $cotizacion->id]) }}" target="_blank" class="flex items-center justify-center gap-2 w-full bg-white border border-blue-200 hover:bg-blue-50 text-blue-700 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-colors">
                                📥 Descargar Presupuesto PDF
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Evidencia fotográfica -->
    @php
        $todasLasEvidencias = [];

        // 1. Extraemos las evidencias directas de la Orden de Servicio
        if (!empty($orden->evidencia_fotografica)) {
            foreach ($orden->evidencia_fotografica as $ev) {
                $todasLasEvidencias[] = $ev;
            }
        }

        // 2. Extraemos las evidencias de la(s) Inspección(es) ligada(s)
        foreach ($orden->inspecciones as $inspeccion) {
            if (!empty($inspeccion->evidencia_fotografica)) {
                foreach ($inspeccion->evidencia_fotografica as $ev) {
                    $todasLasEvidencias[] = $ev;
                }
            }
        }
    @endphp

    @if(count($todasLasEvidencias) > 0)
        <div x-data="{ lightboxOpen: false, lightboxSrc: '', lightboxCaption: '' }" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mt-6">

            <!-- Encabezado -->
            <div class="p-5 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm border border-blue-100/50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold uppercase text-slate-800 tracking-wider">
                        Reporte Fotográfico
                    </h3>
                </div>
                <span class="bg-white border border-slate-200 text-slate-500 text-[10px] py-1 px-3 rounded-full font-black tracking-widest shadow-sm">
            {{ count($todasLasEvidencias) }} {{ count($todasLasEvidencias) == 1 ? 'FOTO' : 'FOTOS' }}
        </span>
            </div>

            <!-- Galería Dinámica -->
            <div class="p-5">
                <div class="grid grid-cols-2 gap-4">
                    @foreach($todasLasEvidencias as $index => $evidencia)
                        @php
                            $urlFoto = \Illuminate\Support\Facades\Storage::disk('s3')->url($evidencia['foto']);
                            $observacionFoto = $evidencia['observacion'] ?? '';
                            $altFoto = $observacionFoto !== '' ? $observacionFoto : 'Evidencia fotográfica del vehículo ' . $vehiculo->placas;
                        @endphp
                        <button type="button"
                           @click="lightboxOpen = true; lightboxSrc = @js($urlFoto); lightboxCaption = @js($observacionFoto)"
                           class="group relative rounded-2xl overflow-hidden bg-slate-100 shadow-sm ring-1 ring-slate-200/60 hover:ring-blue-400 transition-all duration-300 block text-left w-full
                   {{ $index === 0 && count($todasLasEvidencias) % 2 !== 0 ? 'col-span-2 aspect-video' : 'aspect-square' }}">

                            <!-- Imagen con Zoom Cinemático -->
                            <img src="{{ $urlFoto }}" alt="{{ $altFoto }}" loading="lazy" decoding="async"
                                 class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 ease-out">

                            <!-- Overlay Glassmorphism (Aparece en Hover) -->
                            <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/20 transition-colors duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100 z-10">
                                <div class="bg-white/90 backdrop-blur-md p-2.5 rounded-full shadow-xl text-slate-800 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Observación Elegante -->
                            @if($observacionFoto !== '')
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/95 via-slate-900/70 to-transparent p-4 pt-14 z-20">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-blue-400 mt-0.5 shrink-0 opacity-90" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-xs text-white/95 font-medium leading-relaxed line-clamp-2 drop-shadow-md">
                                            {{ $observacionFoto }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Lightbox: ver foto en grande sin salir de la página -->
            <div x-show="lightboxOpen" x-cloak @keydown.escape.window="lightboxOpen = false"
                 class="fixed inset-0 z-50 bg-slate-900/90 flex items-center justify-center p-4"
                 @click="lightboxOpen = false">
                <button type="button" @click="lightboxOpen = false" aria-label="Cerrar"
                        class="absolute top-4 right-4 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-2">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="max-w-3xl w-full" @click.stop>
                    <img :src="lightboxSrc" :alt="lightboxCaption || 'Evidencia fotográfica ampliada'" class="w-full max-h-[80vh] object-contain rounded-xl">
                    <p x-show="lightboxCaption" x-text="lightboxCaption" class="text-white/90 text-sm text-center mt-3"></p>
                </div>
            </div>
        </div>
    @endif

    <div x-data="{ open: false }" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <button @click="open = !open" :aria-expanded="open.toString()" aria-controls="historial-servicios-panel" class="w-full flex justify-between items-center p-5 focus:outline-none">
                <span class="text-sm font-black uppercase text-slate-700 tracking-wider flex items-center gap-2">
                    📚 Historial de Servicios
                    <span class="bg-slate-100 text-slate-600 text-xs py-0.5 px-2 rounded-full">{{ $vehiculo->ordenesServicio->count() }}</span>
                </span>
            <svg :class="{'rotate-180': open}" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <div id="historial-servicios-panel" x-show="open" x-transition class="p-5 pt-0 border-t border-gray-100">
            <div class="relative border-l-2 border-slate-200 ml-3 space-y-6 mt-4">
                @foreach($vehiculo->ordenesServicio->sortByDesc('created_at') as $ordenPasada)
                    <div class="relative pl-6">
                        <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-white border-4
                                {{ $ordenPasada->id == $orden->id ? 'border-blue-500' : 'border-slate-300' }}"></div>

                        <p class="text-xs font-bold text-blue-600 mb-1">{{ $ordenPasada->created_at->format('d M, Y') }}</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-700 text-sm">Folio: {{ $ordenPasada->folio }}</span>
                                <span class="text-[10px] font-bold text-slate-500 uppercase bg-white border border-slate-200 px-2 py-0.5 rounded-full">{{ mb_strtoupper($ordenPasada->estatus) }}</span>
                            </div>

                            @if($ordenPasada->trabajo_a_realizar)
                                <div class="mb-3 text-xs text-slate-600 bg-white p-2 rounded-lg border border-slate-100">
                                    <span class="font-bold block text-slate-400 mb-1">TRABAJO A REALIZAR:</span>
                                    {{ \Illuminate\Support\Str::limit($ordenPasada->trabajo_a_realizar, 120) }}
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-200">

                                @if($ordenPasada->inspecciones->count() > 0)
                                    <a href="{{ route('portal.inspeccion.pdf', $ordenPasada->token_url) }}" target="_blank" class="flex items-center gap-1 text-[10px] bg-white border border-slate-300 hover:bg-slate-100 hover:border-blue-300 hover:text-blue-600 text-slate-600 py-1 px-2 rounded-lg font-bold transition-colors">
                                        📋 Inspección
                                    </a>
                                @endif

                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</main>
</body>
</html>
