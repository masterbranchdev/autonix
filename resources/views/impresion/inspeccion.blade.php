<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inspección - {{ $inspeccion->ordenServicio->folio }}</title>
    <style>
        @page { size: letter; margin: 8mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #111; padding: 0; line-height: 1.2; }

        .header-grid { display: flex; justify-content: space-between; border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 8px; }
        .logo-box { width: 25%; text-align: left; }
        .title-box { width: 50%; text-align: center; }
        .title-box h1 { margin: 0; font-size: 16px; letter-spacing: 1px; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 9px; margin-bottom: 8px; }

        .legend-bar { display: flex; text-align: center; font-weight: bold; font-size: 8px; color: white; margin-bottom: 8px; }
        .bg-verde { background-color: #16a34a; } .bg-amarillo { background-color: #eab308; color: black; } .bg-rojo { background-color: #dc2626; }
        .legend-item { flex: 1; padding: 4px; border: 1px solid #fff; }

        .main-layout { display: flex; gap: 10px; }
        .col-left { width: 62%; } .col-right { width: 38%; }

        /* TABLA DE PUNTOS (Reducimos padding para que quepan 19 renglones) */
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        td { border: 1px solid #ccc; padding: 2px 4px; vertical-align: middle; }
        .td-semaforo { width: 12px; text-align: center; }
        .box-s { width: 10px; height: 10px; border: 1px solid #000; margin: 0 auto; }
        .box-verde { background-color: #16a34a; } .box-amarillo { background-color: #eab308; } .box-rojo { background-color: #dc2626; }

        /* TABLA ADICIONALES */
        .table-add td { padding: 3px; }

        /* DIAGRAMAS (LLANTAS Y BALATAS) */
        .diagram-box { border: 1px solid #000; padding: 4px; margin-bottom: 8px; text-align: center; }
        .diagram-title { font-weight: bold; background: #333; color: #fff; padding: 3px; font-size: 8px; margin-top: -4px; margin-left: -4px; margin-right: -4px; margin-bottom: 5px; }
        .llantas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .corner { border: 1px solid #ddd; padding: 4px; text-align: left; position: relative; font-size: 8px; }
        .corner-lbl { font-weight: bold; font-size: 8px; border-bottom: 1px solid #000; margin-bottom: 2px; }
        .status-bar { height: 4px; width: 100%; margin-top: 2px; }

        @media print { .no-print { display: none; } body { -webkit-print-color-adjust: exact; } }
        /* PIE DE PÁGINA */
        .footer-taller { text-align: center; margin-top: 10px; font-size: 8px; color: #4b5563; border-top: 1px solid #e5e7eb; padding-top: 5px; }


    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="margin-bottom: 10px; padding: 8px 15px; background: #000; color: #fff; border: none; cursor: pointer;">🖨️ Imprimir Reporte</button>

@php
    $taller = $inspeccion->taller ?? auth()->user()->taller;
    $logoBase64 = null;
    if($taller && $taller->logo_path) {
        try {
            $img = \Illuminate\Support\Facades\Storage::disk('s3')->get($taller->logo_path);
            $mime = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($taller->logo_path);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($img);
        } catch (\Exception $e) {}
    }
@endphp

<!-- HEADER -->
<div class="header-grid">
    <div class="logo-box">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-height: 35px;">
            <br>
            <h2>{{ auth()->user()->taller->nombre_comercial }}</h2>
        @else
            <h2>{{ auth()->user()->taller->nombre_comercial }}</h2>
        @endif
    </div>
    <div class="title-box">
        <h1>Hoja de Revisión</h1>
        <p style="margin:2px 0;">REPORTE DE ESTADO DEL VEHÍCULO</p>
    </div>
    <div style="width: 25%; text-align: right; font-size: 9px;">
        <strong>O.S.: {{ $inspeccion->ordenServicio->folio }}</strong><br>
        Fecha: {{ $inspeccion->created_at->format('d/m/Y') }}
    </div>
</div>

@php $vehiculo = $inspeccion->ordenServicio->vehiculo; @endphp
<div class="info-grid">
    <div><strong>Cliente:</strong> {{ $vehiculo->cliente->nombre }}</div>
    <div><strong>Vehículo:</strong> {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->anio }})</div>
    <div><strong>Placas:</strong> {{ $vehiculo->placas }}</div>
    <div><strong>VIN:</strong> {{ $vehiculo->vin ?? 'N/A' }} &nbsp;|&nbsp; <strong>Km:</strong> {{ $vehiculo->kilometraje }}</div>
</div>

<!-- LEYENDA COLORES -->
<div class="legend-bar">
    <div class="legend-item bg-verde">RESULTADO DE LA REVISIÓN O.K.</div>
    <div class="legend-item bg-amarillo">REQUIERE ATENCIÓN EN EL FUTURO</div>
    <div class="legend-item bg-rojo">REQUIERE ATENCIÓN INMEDIATA</div>
</div>

<div class="main-layout">
    <!-- COLUMNA IZQUIERDA: LOS 19 PUNTOS -->
    <div class="col-left">
        <table>
            <tr style="background:#000; color:#fff; text-align:center; font-weight:bold;">
                <td colspan="3">V</td><td colspan="3">A</td><td colspan="3">R</td>
                <td style="width: 75%;">Revisar en cada servicio</td>
            </tr>

            @php
                $add = $inspeccion->adicionales ?? [];
                $fugasEspecificar = isset($add['fugas_especificar']) && $add['fugas_especificar'] != '' ? $add['fugas_especificar'] : '__________________';

                // LOS 19 PUNTOS EXACTOS
                $puntos = [
                    'lavaparabrisas' => 'Fluido lavaparabrisas',
                    'transmision' => 'Nivel y condición del fluido de transmisión automática',
                    'frenos' => 'Nivel y condición del líquido de frenos',
                    'direccion' => 'Nivel y condición del líquido de dirección hidráulica',
                    'anticongelante' => 'Nivel y condición del anticongelante',
                    'transeje_embrague' => 'Transeje, caja de transferencia, nivel y condición del fluido del embrague (si está equipado)',
                    'parabrisas' => 'Cuarteaduras o despostilladuras en el parabrisas',
                    'luces_claxon' => 'Funcionamiento del claxon y de las luces interiores y exteriores',
                    'aspersor_limpiadores' => 'Funcionamiento del aspersor del lavaparabrisas y limpiadores',
                    'enfriamiento' => 'Fugas y/o daños en el sistema de enfriamiento',
                    'fugas_aceite' => 'Fugas de aceite y/o fluidos (especificar: ' . $fugasEspecificar . ')',
                    'cubrepolvos' => 'Cubrepolvos de las flechas de velocidad constante',
                    'escape' => 'Sistema de escape (fugas, daños visibles, partes flojas)',
                    'bandas' => 'Bandas',
                    'direccion_articulaciones' => 'Sistemas de dirección, articulaciones de la dirección, juego longitudinal de los baleros de rueda',
                    'suspension' => 'Suspensión (revisar amortiguadores para ver si están dañados o con fugas)',
                    'lineas_frenos' => 'Líneas y mangueras de frenos, frenos de estacionamiento',
                    'terminales_bateria' => 'Terminales del acumulador (limpiarlas si es necesario)',
                    'embrague' => 'Funcionamiento del embrague (si está equipado)'
                ];
                $data = $inspeccion->check_puntos ?? [];
            @endphp

            @foreach($puntos as $key => $label)
                @php $val = $data[$key] ?? ''; @endphp
                <tr>
                    <td class="td-semaforo"><div class="box-s {{ $val == 'verde' ? 'box-verde' : '' }}"></div></td><td style="border-right:none; border-left:none;"></td><td style="border-left:none;"></td>
                    <td class="td-semaforo"><div class="box-s {{ $val == 'amarillo' ? 'box-amarillo' : '' }}"></div></td><td style="border-right:none; border-left:none;"></td><td style="border-left:none;"></td>
                    <td class="td-semaforo"><div class="box-s {{ $val == 'rojo' ? 'box-rojo' : '' }}"></div></td><td style="border-right:none; border-left:none;"></td><td style="border-left:none;"></td>
                    <td>{{ $label }}</td>
                </tr>
            @endforeach
        </table>

        <!-- SERVICIOS ADICIONALES -->
        <div style="margin-top:8px;">
            <table class="table-add" style="width: 100%;">
                <tr style="background:#333; color:#fff; font-weight:bold;"><td colspan="2">Servicios Adicionales Recomendados</td></tr>
                @php
                    $frenosEspecificar = isset($add['frenos_especificar']) && $add['frenos_especificar'] != '' ? $add['frenos_especificar'] : '__________________';
                @endphp
                <tr><td style="width:12%; text-align:center; font-weight:bold;">{{ isset($add['rotacion']) && $add['rotacion'] ? '[ SÍ ]' : '[   ]' }}</td><td>Rotación de llantas</td></tr>
                <tr><td style="text-align:center; font-weight:bold;">{{ isset($add['filtro_aire']) && $add['filtro_aire'] ? '[ SÍ ]' : '[   ]' }}</td><td>Filtro de aire</td></tr>
                <tr><td style="text-align:center; font-weight:bold;">{{ isset($add['plumas']) && $add['plumas'] ? '[ SÍ ]' : '[   ]' }}</td><td>Plumas de limpiadores</td></tr>
                <tr><td style="text-align:center; font-weight:bold;">{{ isset($add['reparacion_llanta']) && $add['reparacion_llanta'] ? '[ SÍ ]' : '[   ]' }}</td><td>Reparación de llanta</td></tr>
                <tr><td style="text-align:center; font-weight:bold;">{{ isset($add['frenos']) && $add['frenos'] ? '[ SÍ ]' : '[   ]' }}</td><td>Frenos (especificar: {{ $frenosEspecificar }})</td></tr>
                <tr><td style="text-align:center; font-weight:bold;">[   ]</td><td>Otros: {{ $add['otros'] ?? '__________________' }}</td></tr>
            </table>
        </div>

        <div style="margin-top:8px; border:1px solid #ccc; padding:4px; min-height: 25px; font-size:8px;">
            <strong>Observaciones Generales:</strong><br>
            {{ $inspeccion->observaciones_puntos ?? 'Sin observaciones adicionales.' }}
        </div>
    </div>

    <!-- COLUMNA DERECHA: DIAGRAMAS -->
    <div class="col-right">

        <!-- LLANTAS -->
        <div class="diagram-box">
            <div class="diagram-title">Ajustar la presión de las llantas a recomendación</div>
            <div class="llantas-grid">
                @foreach(['di'=>'DI', 'dd'=>'DD', 'ti'=>'TI', 'td'=>'TD'] as $k => $l)
                    @php $ll = $inspeccion->llantas[$k] ?? ['psi'=>'', 'mm'=>'', 'estado'=>'']; @endphp
                    <div class="corner">
                        <div class="corner-lbl">{{ $l }}</div>
                        {{ $ll['psi'] ?: '-' }} PSI <br> {{ $ll['mm'] ?: '-' }} MM
                        <div class="status-bar bg-{{ $ll['estado'] ?? 'verde' }}"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- BALATAS -->
        <div class="diagram-box">
            <div class="diagram-title">Mida las balatas delanteras y traseras</div>
            <div class="llantas-grid">
                @foreach(['di'=>'DI', 'dd'=>'DD', 'ti'=>'TI', 'td'=>'TD'] as $k => $l)
                    @php $bal = $inspeccion->balatas[$k] ?? ['mm'=>'', 'estado'=>'']; @endphp
                    <div class="corner">
                        <div class="corner-lbl">{{ $l }}</div>
                        {{ $bal['mm'] ?: '-' }} MM Grosor
                        <div class="status-bar bg-{{ $bal['estado'] ?? 'verde' }}"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- BATERÍA -->
        <div class="diagram-box">
            <div class="diagram-title">Revisar estado del acumulador</div>
            @php $bat = $inspeccion->bateria ?? ['estado'=>'', 'amperaje'=>'']; @endphp
            <div style="text-align:left; padding: 5px; font-size: 8px;">
                <div><span class="box-s {{ $bat['estado']=='bien' ? 'box-verde' : '' }}" style="display:inline-block; vertical-align:middle;"></span> Bien</div>
                <div style="margin-top:4px;"><span class="box-s {{ $bat['estado']=='mal' ? 'box-rojo' : '' }}" style="display:inline-block; vertical-align:middle;"></span> Mal</div>
                <div style="margin-top:6px; font-weight:bold;">Amperaje de arranque actual: {{ $bat['amperaje'] ?: '___' }} CCA</div>
            </div>
        </div>

    </div>
</div>

<div style="margin-top: 15px; page-break-inside: avoid;">
    <div style="display: flex; justify-content: space-around; text-align: center;">
        <div style="width: 40%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 50px;">
            <div style="width: 100%; border-top: 1px solid #333; padding-top: 4px; font-weight: bold; font-size: 9px;">
                ASESOR DE SERVICIO<br>
                <span style="font-weight: normal; color: #4b5563;">{{ auth()->user()->name ?? 'Asesor' }}</span>
            </div>
        </div>

        <div style="width: 40%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 50px;">
            @if($inspeccion->firma)
                <img src="{{ $inspeccion->firma }}" style="max-height: 40px; max-width: 100%; object-fit: contain; margin-bottom: 2px;">
            @endif
            <div style="width: 100%; border-top: 1px solid #333; padding-top: 4px; font-weight: bold; font-size: 9px;">
                FIRMA DEL CLIENTE<br>
                <span style="font-weight: normal; color: #4b5563;">{{ $inspeccion->ordenServicio->vehiculo->cliente->nombre }}</span>
            </div>
        </div>
    </div>
</div>

@if($inspeccion->taller)
    <div class="footer-taller" style="text-align: center; margin-top: 15px; font-size: 8px; color: #4b5563; border-top: 1px solid #e5e7eb; padding-top: 5px;">
        @if($inspeccion->taller->logo_path)
            <img src="{{ $logoBase64 }}" style="max-height: 25px; margin-bottom: 3px;"><br>
        @endif
        <div style="font-weight: bold; font-size: 9px; margin-bottom: 2px;">{{ $inspeccion->taller->nombre_comercial }}</div>
        <div style="margin-bottom: 2px;">{{ $inspeccion->taller->domicilio }}</div>
        <div>
            Tel: {{ $inspeccion->taller->telefono ?? 'N/A' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            WhatsApp: {{ $inspeccion->taller->whatsapp_publico ?? 'N/A' }}
        </div>
    </div>
@endif

</body>
</html>
