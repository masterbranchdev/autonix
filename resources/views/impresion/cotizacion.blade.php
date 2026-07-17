<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización - {{ $cotizacion->folio }}</title>
    <style>
        @page { size: letter; margin: 10mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.3; padding: 0; }

        .header-grid { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111827; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-box { width: 30%; }
        .title-box { width: 40%; text-align: center; }
        .title-box h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 2px; color: #111827; }
        .folio-box { width: 30%; text-align: right; }
        .folio-box h3 { margin: 0; font-size: 18px; color: #ef4444; }

        .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; border: 1px solid #d1d5db; border-radius: 6px; padding: 12px; background: #f9fafb; }
        .info-col { width: 48%; }
        .info-row { margin-bottom: 4px; }
        .info-label { font-weight: bold; color: #4b5563; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background-color: #111827; color: white; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        table.items td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        .footer-grid { display: flex; justify-content: space-between; margin-top: 10px; }
        .notes-box { width: 55%; font-size: 10px; color: #4b5563; padding: 10px; border: 1px dashed #d1d5db; border-radius: 6px; }
        .totals-box { width: 38%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }

        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .grand-total { font-weight: bold; font-size: 16px; border-top: 2px solid #111827; margin-top: 5px; padding-top: 8px; color: #16a34a; }

        .firmas { display: flex; justify-content: space-around; margin-top: 60px; text-align: center; page-break-inside: avoid; }
        .firma-line { width: 40%; border-top: 1px solid #111827; padding-top: 5px; font-weight: bold; font-size: 10px; color: #4b5563; }

        @media print { .no-print { display: none; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>

<button class="no-print" onclick="window.print()" style="margin-bottom: 15px; padding: 8px 15px; background: #2563eb; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">🖨️ Imprimir Presupuesto</button>

@php
    // 1. Obtenemos el taller directo de la cotización o del usuario actual
    $taller = \App\Models\Taller::find($cotizacion->taller_id);

    // 2. Evaluamos si hay orden de servicio de forma segura
    $orden = $cotizacion->ordenServicio;
    $vehiculo = $orden ? $orden->vehiculo : null;
    $cliente = $vehiculo ? $vehiculo->cliente : null;

    $asesor = auth()->check() ? auth()->user()->name : 'Asesor de Servicio';

    $logoBase64 = null;
    if($taller && $taller->logo_path) {
        try {
            $img = \Illuminate\Support\Facades\Storage::disk('s3')->get($taller->logo_path);
            $mime = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($taller->logo_path);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($img);
        } catch (\Exception $e) {}
    }
@endphp

<div class="header-grid">
    <div class="logo-box">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-height: 50px;">
            <br>
            <h2 style="margin:0;">{{ $taller->nombre_comercial ?? 'TALLER' }}</h2>
        @else
            <h2 style="margin:0;">{{ $taller->nombre_comercial ?? 'TALLER' }}</h2>
        @endif
    </div>
    <div class="title-box">
        <h1>Cotización</h1>
        <div style="font-weight: bold; color: #6b7280; font-size: 12px; margin-top: 4px;">ESTATUS: {{ strtoupper($cotizacion->estatus) }}</div>
    </div>
    <div class="folio-box">
        <span style="font-size: 9px; color: #6b7280; font-weight: bold;">FOLIO:</span>
        <h3>{{ $cotizacion->folio }}</h3>
        <div style="font-size: 9px; margin-top: 4px;">Fecha: {{ $cotizacion->created_at->format('d/m/Y') }}</div>
    </div>
</div>

<div class="info-grid">
    <div class="info-col">
        <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px;">Atención a:</div>
        <div class="info-row"><span class="info-label">Cliente:</span> {{ $cliente ? $cliente->nombre : 'Cotización General' }}</div>
        <div class="info-row"><span class="info-label">Teléfono:</span> {{ $cliente ? $cliente->telefono : 'N/A' }}</div>
        <div class="info-row"><span class="info-label">Email:</span> {{ ($cliente && $cliente->email) ? $cliente->email : 'N/A' }}</div>
    </div>
    <div class="info-col">
        <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px;">Datos del Vehículo:</div>
        <div class="info-row"><span class="info-label">Unidad:</span> {{ $vehiculo ? ($vehiculo->marca . ' ' . $vehiculo->modelo . ' (' . $vehiculo->anio . ')') : 'Vehículo no registrado' }}</div>
        <div class="info-row"><span class="info-label">Placas:</span> {{ $vehiculo ? $vehiculo->placas : 'N/A' }} &nbsp;|&nbsp; <span class="info-label">Color:</span> {{ $vehiculo ? $vehiculo->color : 'N/A' }}</div>
        <div class="info-row"><span class="info-label">VIN:</span> {{ ($vehiculo && $vehiculo->vin) ? $vehiculo->vin : 'N/A' }}</div>
    </div>
</div>

<table class="items">
    <thead>
    <tr>
        <th style="width: 10%;">CANT.</th>
        <th style="width: 50%;">CONCEPTO / DESCRIPCIÓN</th>
        <th style="width: 20%;" class="text-right">PRECIO UNIT.</th>
        <th style="width: 20%;" class="text-right">IMPORTE</th>
    </tr>
    </thead>
    <tbody>
    @forelse($cotizacion->items as $item)
        <tr>
            <td class="text-center">{{ $item->cantidad }}</td>
            <td>{{ $item->descripcion }}</td>
            <td class="text-right">${{ number_format($item->precio_unitario, 2) }}</td>
            <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-center">No hay conceptos registrados.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer-grid">
    <div class="notes-box">
        <strong>NOTAS Y CONDICIONES:</strong><br>
        <div style="white-space: pre-line; margin-top: 5px;">{{ $cotizacion->notas ?: 'Los precios están sujetos a cambios sin previo aviso.' }}</div>
    </div>

    <div class="totals-box">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($cotizacion->subtotal + $cotizacion->descuento, 2) }}</span>
        </div>
        @if($cotizacion->descuento > 0)
            <div class="total-row" style="color: #dc2626;">
                <span>Descuento:</span>
                <span>- ${{ number_format($cotizacion->descuento, 2) }}</span>
            </div>
        @endif
        <div class="total-row">
            <span>I.V.A.:</span>
            <span>${{ number_format($cotizacion->iva, 2) }}</span>
        </div>
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>${{ number_format($cotizacion->total, 2) }} MXN</span>
        </div>
    </div>
</div>

<div class="firmas">
    <div class="firma-line">
        ELABORÓ<br>
        <span style="font-weight: normal; font-size: 9px;">{{ auth()->user()->name }} - Asesor de Servicio</span>
    </div>
    <div class="firma-line">
        ACEPTACIÓN DEL CLIENTE<br>
        <span style="font-weight: normal; font-size: 9px;">Autorizo la realización de los trabajos aquí descritos</span>
    </div>
</div>

@if($taller)
    <div style="text-align: center; margin-top: 40px; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px;">
        <strong>{{ $taller->nombre_comercial }}</strong> | {{ $taller->domicilio }}<br>
        Tel: {{ $taller->telefono }} | WhatsApp: {{ $taller->whatsapp_publico }}
    </div>
@endif

</body>
</html>
