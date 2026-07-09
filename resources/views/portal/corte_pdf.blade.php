<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte Financiero Ejecutivo</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; color: #111; text-transform: uppercase; }

        .summary-box { width: 100%; margin-bottom: 20px; border-collapse: collapse; text-align: center; }
        .summary-box td { padding: 15px; border: 1px solid #ddd; background: #f8fafc; width: 33.33%; }
        .summary-box h3 { margin: 0 0 5px 0; font-size: 10px; color: #64748b; text-transform: uppercase; }
        .amount { font-size: 18px; font-weight: bold; }

        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }

        /* Magia CSS para Gráficas en PDF */
        .chart-container { width: 100%; border: 1px solid #e2e8f0; padding: 15px; margin-bottom: 20px; background: #fff; border-radius: 8px; }
        .bar-row { margin-bottom: 12px; }
        .bar-label { font-weight: bold; margin-bottom: 4px; font-size: 10px; }
        .bar-wrap { width: 100%; background: #f1f5f9; height: 18px; border-radius: 4px; overflow: hidden; position: relative; }
        .bar-fill { height: 100%; position: absolute; left: 0; top: 0; }

        table.details { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9px; }
        table.details th { background: #0f172a; color: #fff; padding: 8px; text-align: left; }
        table.details td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .row-ingreso { background: #f0fdf4; }
        .row-egreso { background: #fef2f2; }
    </style>
</head>
<body>

@php
    // Calculamos el porcentaje visual de las barras
    $maxAmount = max($ingresos, $egresos);
    $maxAmount = $maxAmount > 0 ? $maxAmount : 1;
    $pctIngresos = ($ingresos / $maxAmount) * 100;
    $pctEgresos = ($egresos / $maxAmount) * 100;

    $logoBase64 = null;
    if(isset($taller) && $taller->logo_path) {
        try {
            $img = \Illuminate\Support\Facades\Storage::disk('s3')->get($taller->logo_path);
            $mime = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($taller->logo_path);
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($img);
        } catch (\Exception $e) {}
    }
@endphp

<div class="header">
    <div class="header">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-height: 45px; margin-bottom: 10px;">
        @endif
        <h1>{{ $orden->taller->nombre_comercial ?? auth()->user()->taller->nombre_comercial ?? 'TALLER MECÁNICO' }} <br> Corte Financiero Ejecutivo</h1>
    <p>Periodo reportado: <strong>{{ date('d/m/Y', strtotime($inicio)) }}</strong> al <strong>{{ date('d/m/Y', strtotime($fin)) }}</strong></p>
</div>

<!-- 1. BLOQUE DE RESUMEN (TARJETAS) -->
<table class="summary-box">
    <tr>
        <td>
            <h3>Total Ingresos</h3>
            <div class="amount text-green">${{ number_format($ingresos, 2) }}</div>
        </td>
        <td>
            <h3>Total Egresos</h3>
            <div class="amount text-red">-${{ number_format($egresos, 2) }}</div>
        </td>
        <td>
            <h3>Utilidad Neta (Balance)</h3>
            <div class="amount text-blue">${{ number_format($balance, 2) }}</div>
        </td>
    </tr>
</table>

<!-- 2. BLOQUE DE GRÁFICA COMPARATIVA -->
<div class="chart-container">
    <h3 style="margin-top:0; font-size: 12px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Comparativa Financiera del Periodo</h3>

    <div class="bar-row">
        <div class="bar-label text-green">FLUJO POSITIVO (INGRESOS: ${{ number_format($ingresos, 2) }})</div>
        <div class="bar-wrap">
            <div class="bar-fill" style="width: {{ $pctIngresos }}%; background: #16a34a;"></div>
        </div>
    </div>

    <div class="bar-row">
        <div class="bar-label text-red">FLUJO NEGATIVO (EGRESOS: ${{ number_format($egresos, 2) }})</div>
        <div class="bar-wrap">
            <div class="bar-fill" style="width: {{ $pctEgresos }}%; background: #dc2626;"></div>
        </div>
    </div>
</div>

<!-- 3. DETALLE DE MOVIMIENTOS -->
<h3 style="font-size: 12px; margin-bottom: 5px;">Desglose de Movimientos</h3>
<table class="details">
    <thead>
    <tr>
        <th style="width: 12%;">FECHA</th>
        <th style="width: 8%;">TIPO</th>
        <th style="width: 32%;">CONCEPTO / DESCRIPCIÓN</th>
        <th style="width: 12%;">MÉTODO</th>
        <th style="width: 13%;">REFERENCIA</th>
        <th style="width: 8%; text-align: center;">FACTURA</th> <th style="width: 15%; text-align:right;">MONTO</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transacciones as $t)
        <tr class="{{ $t->tipo == 'Ingreso' ? 'row-ingreso' : 'row-egreso' }}">
            <td>{{ date('d/m/Y', strtotime($t->fecha)) }}</td>
            <td class="{{ $t->tipo == 'Ingreso' ? 'text-green' : 'text-red' }}"><strong>{{ $t->tipo }}</strong></td>
            <td>{{ $t->concepto }}</td>
            <td>{{ $t->metodo_pago }}</td>
            <td style="font-size: 8px; color: #475569;">{{ $t->referencia ?: 'N/A' }}</td>

            <td style="text-align: center; font-size: 9px;">
                @if($t->requiere_factura)
                    <span style="color: #16a34a; font-weight: bold;">SÍ</span>
                @else
                    <span style="color: #94a3b8;">NO</span>
                @endif
            </td>

            <td style="text-align:right; font-weight:bold; font-size: 10px;">
                {{ $t->tipo == 'Ingreso' ? '+' : '-' }}${{ number_format($t->monto, 2) }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div style="margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8;">
    Reporte generado el {{ now()->format('d/m/Y h:i A') }}
</div>

</body>
</html>
