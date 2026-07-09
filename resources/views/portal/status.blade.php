<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente Digital - {{ $orden->vehiculo->placas }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .tracker-step { transition: all 0.3s ease; }
        .tracker-active { background-color: #2563eb; color: white; border-color: #2563eb; }
        .tracker-done { background-color: #10b981; color: white; border-color: #10b981; }
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

    // Mapeo lógico de los estatus para el Tracker
    $estatusActual = strtolower($orden->estatus);

    $pasos = [
        ['id' => 'ingresado', 'label' => 'Ingresado', 'icon' => '📋'],
        ['id' => 'diagnosticando', 'label' => 'Revisión', 'icon' => '🔍'],
        ['id' => 'cotizando', 'label' => 'Cotizando', 'icon' => '💲'],
        ['id' => 'reparacion', 'label' => 'En Taller', 'icon' => '🔧'],
        ['id' => 'calidad', 'label' => 'Revisión Final', 'icon' => '⚙️'], // <-- NUEVO PASO
        ['id' => 'listo', 'label' => 'Listo', 'icon' => '🏁'],
        ['id' => 'entregado', 'label' => 'Entregado', 'icon' => '✅'],// <-- Icono actualizado
    ];

    // Definimos en qué número de paso vamos según el estatus real
    $pasoActual = 1;
    if(str_contains($estatusActual, 'revis') || str_contains($estatusActual, 'diagnost')) $pasoActual = 2;
    if(str_contains($estatusActual, 'espera') || str_contains($estatusActual, 'cotiz')) $pasoActual = 3;
    if(str_contains($estatusActual, 'reparacion') || str_contains($estatusActual, 'repara')) $pasoActual = 4;
    if(str_contains($estatusActual, 'calidad') || str_contains($estatusActual, 'final')) $pasoActual = 5;
    if(str_contains($estatusActual, 'terminado') || str_contains($estatusActual, 'listo')) $pasoActual = 6;
    if(str_contains($estatusActual, 'entregado')) $pasoActual = 7; // <--- NUEVA LÓGICA
@endphp

<header class="bg-slate-900 text-white p-6 shadow-md rounded-b-3xl">
    <div class="max-w-md mx-auto text-center">
        @if($taller && $taller->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($taller->logo_path) }}" class="mx-auto h-16 mb-2">
        @else
            <h1 class="text-2xl font-black tracking-widest uppercase">{{ $taller->nombre_comercial ?? 'Autonix' }}</h1>
        @endif
        <p class="text-xs text-slate-400 mt-2 uppercase tracking-widest">Expediente Digital del Vehículo</p>
    </div>
</header>

<main class="max-w-lg mx-auto p-4 space-y-6 mt-2">

    <div class="bg-white rounded-3xl shadow-sm p-6 border border-gray-100">
        <h2 class="text-center font-bold text-gray-800 mb-6 uppercase tracking-wider text-sm">Rastreo en vivo</h2>

        <div class="relative flex justify-between items-center w-full">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 z-0 rounded-full"></div>
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-1 bg-emerald-500 z-0 rounded-full transition-all duration-500" style="width: {{ (($pasoActual - 1) / (count($pasos) - 1)) * 100 }}%"></div>

            @foreach($pasos as $index => $paso)
                @php
                    $numeroPaso = $index + 1;
                    if($numeroPaso < $pasoActual) { $clase = 'tracker-done'; }
                    elseif($numeroPaso == $pasoActual) { $clase = 'tracker-active shadow-lg shadow-blue-200 scale-110'; }
                    else { $clase = 'tracker-pending'; }
                @endphp

                <div class="relative z-10 flex flex-col items-center group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg border-2 {{ $clase }}">
                        {{ $paso['icon'] }}
                    </div>
                    <span class="text-[10px] font-bold mt-2 text-center absolute -bottom-5 w-20
                            {{ $numeroPaso == $pasoActual ? 'text-blue-600' : 'text-gray-400' }}">
                            {{ $paso['label'] }}
                        </span>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <p class="text-2xl font-black text-slate-800">{{ strtoupper($orden->estatus) }}</p>
            <p class="text-sm text-gray-500 mt-1">Última actualización: {{ $orden->updated_at->diffForHumans() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-5 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400 font-bold uppercase">Unidad</p>
            <p class="text-lg font-black text-slate-800">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</p>
            <p class="text-sm text-gray-500">{{ $vehiculo->placas }} • {{ $vehiculo->color }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 font-bold uppercase">Ingreso</p>
            <p class="text-sm font-bold text-slate-800">{{ $orden->created_at->format('d M, Y') }}</p>
            <p class="text-xs text-gray-500">Folio: {{ $orden->folio }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-5 border border-gray-100">
        <h3 class="text-sm font-black uppercase text-slate-700 tracking-wider mb-3">Documentos Oficiales</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('portal.orden.pdf', $orden->token_url) }}" target="_blank" class="flex flex-col items-center justify-center gap-1 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-blue-300 text-slate-700 hover:text-blue-700 py-3 px-2 rounded-2xl text-xs font-bold transition-all">
                <span class="text-xl">📄</span>
                Orden de Ingreso
            </a>
            <a href="{{ route('portal.inspeccion.pdf', $orden->token_url) }}" target="_blank" class="flex flex-col items-center justify-center gap-1 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-blue-300 text-slate-700 hover:text-blue-700 py-3 px-2 rounded-2xl text-xs font-bold transition-all">
                <span class="text-xl">📋</span>
                Hoja de Inspección
            </a>
        </div>
    </div>

    @if($orden->cotizaciones->count() > 0)
        <div class="bg-white rounded-3xl shadow-sm p-1 border border-blue-100">
            <div class="bg-blue-50 rounded-[22px] p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-black uppercase text-blue-900 tracking-wider">Presupuestos Actuales</h3>
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

    <div x-data="{ open: false }" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <button @click="open = !open" class="w-full flex justify-between items-center p-5 focus:outline-none">
                <span class="text-sm font-black uppercase text-slate-700 tracking-wider flex items-center gap-2">
                    📚 Historial de Servicios
                    <span class="bg-slate-100 text-slate-600 text-xs py-0.5 px-2 rounded-full">{{ $vehiculo->ordenesServicio->count() }}</span>
                </span>
            <svg :class="{'rotate-180': open}" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <div x-show="open" x-transition class="p-5 pt-0 border-t border-gray-100">
            <div class="relative border-l-2 border-slate-200 ml-3 space-y-6 mt-4">
                @foreach($vehiculo->ordenesServicio->sortByDesc('created_at') as $ordenPasada)
                    <div class="relative pl-6">
                        <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-white border-4
                                {{ $ordenPasada->id == $orden->id ? 'border-blue-500' : 'border-slate-300' }}"></div>

                        <p class="text-xs font-bold text-blue-600 mb-1">{{ $ordenPasada->created_at->format('d M, Y') }}</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-700 text-sm">Folio: {{ $ordenPasada->folio }}</span>
                                <span class="text-[10px] font-bold text-slate-500 uppercase bg-white border border-slate-200 px-2 py-0.5 rounded-full">{{ $ordenPasada->estatus }}</span>
                            </div>

                            @if($ordenPasada->trabajo_a_realizar)
                                <div class="mb-3 text-xs text-slate-600 bg-white p-2 rounded-lg border border-slate-100">
                                    <span class="font-bold block text-slate-400 mb-1">TRABAJO A REALIZAR:</span>
                                    {{ Str::limit($ordenPasada->trabajo_a_realizar, 120) }}
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-200">
                                <a href="{{ route('portal.orden.pdf', $ordenPasada->token_url) }}" target="_blank" class="flex items-center gap-1 text-[10px] bg-white border border-slate-300 hover:bg-slate-100 hover:border-blue-300 hover:text-blue-600 text-slate-600 py-1 px-2 rounded-lg font-bold transition-colors">
                                    📄 Orden
                                </a>

                                @if($ordenPasada->inspecciones->count() > 0)
                                    <a href="{{ route('portal.inspeccion.pdf', $ordenPasada->token_url) }}" target="_blank" class="flex items-center gap-1 text-[10px] bg-white border border-slate-300 hover:bg-slate-100 hover:border-blue-300 hover:text-blue-600 text-slate-600 py-1 px-2 rounded-lg font-bold transition-colors">
                                        📋 Inspección
                                    </a>
                                @endif

                                @foreach($ordenPasada->cotizaciones->where('estatus', 'Aprobada') as $cotizacionPasada)
                                    <a href="{{ route('portal.cotizacion.pdf', ['token' => $ordenPasada->token_url, 'id' => $cotizacionPasada->id]) }}" target="_blank" class="flex items-center gap-1 text-[10px] bg-white border border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 text-emerald-600 py-1 px-2 rounded-lg font-bold transition-colors">
                                        📥 Presupuesto {{ $cotizacionPasada->folio }}
                                    </a>
                                @endforeach
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
