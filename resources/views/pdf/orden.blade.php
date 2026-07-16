<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Servicio - {{ $orden->folio }}</title>
    <style>
        @page { size: letter; margin: 8mm 10mm; }

        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0; font-size: 11px; color: #1f2937; line-height: 1.3; }

        .header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .header-logo { width: 33%; font-size: 16px; font-weight: 900; color: #111827; text-transform: uppercase; margin-top: 5px; }
        .header-title { width: 34%; text-align: center; }
        .header-title h2 { margin: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; }
        .header-title p { margin: 4px 0 0 0; font-size: 10px; color: #4b5563; }
        .header-folio-wrapper { width: 33%; display: flex; justify-content: flex-end; }
        .header-folio { text-align: center; border: 2px solid #ef4444; padding: 4px 15px; border-radius: 6px; }
        .header-folio span { font-size: 9px; color: #6b7280; font-weight: bold; }
        .header-folio h3 { margin: 2px 0 0 0; color: #ef4444; font-size: 16px; }

        .section-title { background-color: #f3f4f6; padding: 4px 8px; font-weight: bold; text-transform: uppercase; font-size: 10px; margin-bottom: 8px; border-radius: 3px; border-left: 3px solid #3b82f6; }
        .flex-row { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 12px; }
        .box { border: 1px solid #d1d5db; padding: 8px 10px; border-radius: 4px; }
        .col-60 { width: 58%; }
        .col-40 { width: 38%; }
        .datos-grid { display: grid; grid-template-columns: 1fr 1fr; row-gap: 4px; column-gap: 15px; }
        .info-row { display: flex; align-items: flex-end; }
        .info-label { font-weight: bold; color: #374151; font-size: 10px; margin-right: 5px; white-space: nowrap; }
        .info-value { flex: 1; border-bottom: 1px dashed #d1d5db; font-size: 10px; padding-bottom: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .info-row-full { display: flex; align-items: flex-end; margin-bottom: 5px; width: 100%; }

        .checklist-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
        .check-item { display: flex; align-items: center; font-size: 10px; color: #374151; }
        .check-box { display: inline-block; width: 10px; height: 10px; border: 1px solid #4b5563; margin-right: 5px; text-align: center; line-height: 10px; font-weight: bold; font-size: 9px; border-radius: 2px; }
        .badges-container { display: flex; flex-wrap: wrap; gap: 4px; }
        .badge { background-color: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: bold; border: 1px solid #fca5a5; }

        .car-grid { display: flex; justify-content: space-between; margin-top: 5px; }
        .car-canvas { width: 23%; height: 120px; position: relative; text-align: center; font-size: 10px; font-weight: bold; color: #4b5563; }
        .car-base-img, .car-drawing { position: absolute; top: 0; left: 0; width: 100%; height: 120px; object-fit: contain; }
        .car-base-img { z-index: 1; }
        .car-drawing { z-index: 10; }

        .firmas-container { margin-top: 10px; page-break-inside: avoid; }
        .firmas-grid { display: flex; justify-content: space-around; }
        .firma-box { width: 40%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 60px; text-align: center; }
        .firma-img { max-height: 40px; max-width: 100%; object-fit: contain; }
        .firma-line { width: 100%; border-top: 1px solid #374151; padding-top: 2px; font-weight: bold; font-size: 9px; }

        .footer-taller { text-align: center; margin-top: 10px; font-size: 8px; color: #4b5563; border-top: 1px solid #e5e7eb; padding-top: 5px; }

        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="margin-bottom: 15px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold;">
    🖨️ Imprimir Orden de Servicio
</button>

@php
    $taller = $orden->taller;
    $asesor = auth()->check() ? auth()->user()->name : 'Asesor de Servicio';

    // 1. MAGIA BASE 64 PARA EL LOGO
    $logoBase64 = null;
    if($taller && $taller->logo_path) {
        try {
            $img = \Illuminate\Support\Facades\Storage::disk('s3')->get($taller->logo_path);
            $mime = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($taller->logo_path);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($img);
        } catch (\Exception $e) {}
    }

    // 2. MAGIA BASE 64 PARA LA FIRMA
    $firmaBase64 = null;
    if($orden->firma) {
        if(str_starts_with($orden->firma, 'data:image')) {
            $firmaBase64 = $orden->firma;
        } else {
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk('public')->exists($orden->firma) ? 'public' : 's3';
                $imgFirma = \Illuminate\Support\Facades\Storage::disk($disk)->get($orden->firma);
                $mimeFirma = \Illuminate\Support\Facades\Storage::disk($disk)->mimeType($orden->firma);
                $firmaBase64 = 'data:' . $mimeFirma . ';base64,' . base64_encode($imgFirma);
            } catch (\Exception $e) {}
        }
    }
@endphp

<div class="header">
    <div class="header-logo">
        {{ $taller->nombre_comercial ?? 'TALLER MECÁNICO' }}
    </div>

    <div class="header-title">
        <h2>Orden de Servicio</h2>
        <p>Mantenimiento y Reparación Automotriz</p>
    </div>

    <div class="header-folio-wrapper">
        <div class="header-folio">
            <span>NÚMERO DE FOLIO</span>
            <h3>{{ $orden->folio }}</h3>
        </div>
    </div>
</div>

<div class="flex-row">
    <div class="box col-60">
        <div class="section-title">Datos del Vehículo</div>
        <div class="datos-grid">
            <div class="info-row"><div class="info-label">Marca:</div><div class="info-value">{{ $orden->vehiculo->marca }}</div></div>
            <div class="info-row"><div class="info-label">Modelo:</div><div class="info-value">{{ $orden->vehiculo->modelo }}</div></div>
            <div class="info-row"><div class="info-label">Color:</div><div class="info-value">{{ $orden->vehiculo->color }}</div></div>
            <div class="info-row"><div class="info-label">Placas:</div><div class="info-value">{{ $orden->vehiculo->placas }}</div></div>
            <div class="info-row"><div class="info-label">Km:</div><div class="info-value">{{ $orden->vehiculo->kilometraje }}</div></div>
            <div class="info-row"><div class="info-label">VIN:</div><div class="info-value">{{ $orden->vehiculo->vin ?? 'N/A' }}</div></div>
            <div class="info-row"><div class="info-label">Tarjeta Circ.:</div><div class="info-value">{{ $orden->vehiculo->tarjeta_circulacion ?? 'N/A' }}</div></div>
            <div class="info-row"><div class="info-label">Póliza:</div><div class="info-value">{{ $orden->vehiculo->poliza_seguro ?? 'N/A' }}</div></div>
        </div>
        <div class="info-row-full" style="margin-top: 8px;">
            <div class="info-label">¿Ingreso en grúa?</div>
            <div class="info-value" style="border: none;"><strong>{{ $orden->ingreso_grua ? 'SÍ [X]  NO [ ]' : 'SÍ [ ]  NO [X]' }}</strong></div>
        </div>
    </div>

    <div class="box col-40">
        <div class="section-title">Datos del Cliente</div>
        <div class="info-row-full"><div class="info-label">Nombre:</div><div class="info-value">{{ $orden->vehiculo->cliente->nombre }}</div></div>
        <div class="info-row-full"><div class="info-label">Teléfono:</div><div class="info-value">{{ $orden->vehiculo->cliente->telefono }}</div></div>
        <div class="info-row-full"><div class="info-label">Email:</div><div class="info-value">{{ $orden->vehiculo->cliente->email ?? 'N/A' }}</div></div>
        <div class="info-row-full"><div class="info-label">Ingreso:</div><div class="info-value">{{ \Carbon\Carbon::parse($orden->fecha_ingreso)->format('d/m/Y h:i A') }}</div></div>
    </div>
</div>

<div class="box" style="margin-bottom: 12px; width: 100%; box-sizing: border-box;">
    <div class="section-title">Trabajo a Realizar</div>
    <div style="min-height: 40px; white-space: pre-line; padding: 2px;">{{ $orden->trabajo_a_realizar ?: 'Sin especificar.' }}</div>
</div>

<div class="box" style="margin-bottom: 12px; width: 100%; box-sizing: border-box;">
    <div class="section-title">Observaciones</div>
    <div style="min-height: 40px; white-space: pre-line; padding: 2px;">{{ $orden->observaciones ?: 'Ninguna.' }}</div>
</div>

<div class="flex-row">
    <div class="box col-60">
        <div class="section-title">Inventario del Vehículo</div>
        @php
            $opcionesInventario = [
                'gato' => 'Gato', 'herramientas' => 'Herramientas', 'triangulos' => 'Triángulos',
                'tapetes' => 'Tapetes', 'llanta_refaccion' => 'Llanta refacción', 'extintor' => 'Extintor',
                'antena' => 'Antena', 'emblemas' => 'Emblemas', 'estereo' => 'Estéreo', 'encendedor' => 'Encendedor'
            ];
            $inventarioSeleccionado = $orden->inventario ?? [];
        @endphp
        <div class="checklist-grid">
            @foreach($opcionesInventario as $key => $label)
                <div class="check-item">
                    <span class="check-box">{{ in_array($key, $inventarioSeleccionado) ? 'X' : '' }}</span> {{ $label }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="box col-40" style="display: flex; gap: 10px;">
        <div style="width: 65%;">
            <div class="section-title">Testigos Tablero</div>
            @php
                $opcionesTestigos = [
                    'check_engine' => 'Check Engine', 'abs' => 'ABS', 'aceite' => 'Presión de Aceite',
                    'bateria' => 'Batería', 'temperatura' => 'Temperatura', 'freno_mano' => 'Freno (P)',
                    'bolsas_aire' => 'Bolsas de Aire', 'llantas' => 'Llantas', 'luces_altas' => 'Luces', 'traccion' => 'Tracción'
                ];
                $testigosSeleccionados = $orden->testigos ?? [];
            @endphp
            @if(empty($testigosSeleccionados))
                <p style="font-size: 10px; color: #6b7280; margin: 5px 0;">Ningún testigo encendido.</p>
            @else
                <div class="badges-container">
                    @foreach($testigosSeleccionados as $key)
                        @if(isset($opcionesTestigos[$key]))
                            <span class="badge">⚠️ {{ $opcionesTestigos[$key] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div style="width: 35%; text-align: center; border-left: 1px solid #d1d5db; padding-left: 5px;">
            <div class="section-title" style="border-left: none; padding: 4px;">Gasolina</div>
            <div style="font-size: 16px; font-weight: bold; margin-top: 15px; color: #111827;">
                [ {{ $orden->nivel_gasolina ?? 'N/A' }} ]
            </div>
        </div>
    </div>
</div>

<div class="box" style="margin-bottom: 12px; width: 100%; box-sizing: border-box;">
    <div class="section-title">Daños Preexistentes del Vehículo</div>
    <div class="car-grid">
        <div class="car-canvas">
            <img src="{{ asset('img/auto-derecho.jpg') }}" class="car-base-img">
            @if(isset($orden->danios_carroceria['lado_derecho']))<img src="{{ $orden->danios_carroceria['lado_derecho'] }}" class="car-drawing">@endif
        </div>
        <div class="car-canvas">
            <img src="{{ asset('img/auto-frente.jpg') }}" class="car-base-img">
            @if(isset($orden->danios_carroceria['frente']))<img src="{{ $orden->danios_carroceria['frente'] }}" class="car-drawing">@endif
        </div>
        <div class="car-canvas">
            <img src="{{ asset('img/auto-detras.jpg') }}" class="car-base-img">
            @if(isset($orden->danios_carroceria['detras']))<img src="{{ $orden->danios_carroceria['detras'] }}" class="car-drawing">@endif
        </div>
        <div class="car-canvas">
            <img src="{{ asset('img/auto-izquierdo.jpg') }}" class="car-base-img">
            @if(isset($orden->danios_carroceria['lado_izquierdo']))<img src="{{ $orden->danios_carroceria['lado_izquierdo'] }}" class="car-drawing">@endif
        </div>
    </div>
</div>

<div class="firmas-container">
    <div class="firmas-grid">
        <div class="firma-box">
            <div class="firma-line">
                TALLER / ASESOR DE SERVICIO<br>
                <span style="font-weight: normal; font-size: 10px; color: #4b5563;">{{ $asesor }}</span>
            </div>
        </div>
        <div class="firma-box">
            @if($firmaBase64)
                <img src="{{ $firmaBase64 }}" class="firma-img" alt="Firma">
            @endif
            <div class="firma-line">
                FIRMA DE CONFORMIDAD<br>
                <span style="font-weight: normal; font-size: 10px; color: #4b5563;">
                        {{ $orden->persona_que_entrega ?: ($orden->vehiculo->cliente->nombre ?? 'Firma del Cliente') }}
                    </span>
            </div>
        </div>
    </div>
</div>

@if($taller)
    <div class="footer-taller" style="text-align: center; margin-top: 15px; font-size: 8px; color: #4b5563; border-top: 1px solid #e5e7eb; padding-top: 5px;">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-height: 25px; margin-bottom: 3px;"><br>
        @endif
        <div style="font-weight: bold; font-size: 9px; margin-bottom: 2px;">{{ $taller->nombre_comercial }}</div>
        <div style="margin-bottom: 2px;">{{ $taller->domicilio }}</div>
        <div>
            Tel: {{ $taller->telefono ?? 'N/A' }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            WhatsApp: {{ $taller->whatsapp_publico ?? 'N/A' }}
        </div>
    </div>
@endif

</body>
</html>
