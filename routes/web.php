<?php

use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Transaccion;
use Illuminate\Http\Request;

//Route::view('/', 'welcome');

//Route::view('dashboard', 'dashboard')
//    ->middleware(['auth', 'verified'])
//    ->name('dashboard');
//
//Route::view('profile', 'profile')
//    ->middleware(['auth'])
//    ->name('profile');

// --- RUTAS DE AUTONIX SAAS ---
// Ruta para imprimir la Orden de Servicio en PDF/Web
Route::get('/orden-servicio/{orden}/imprimir', function (\App\Models\OrdenServicio $orden) {
    return view('impresion.orden', ['orden' => $orden]);
})->middleware(['auth'])->name('orden.imprimir');



Route::get('/inspeccion/{inspeccion}/imprimir', function (\App\Models\Inspeccion $inspeccion) {
    // Cargamos la inspección con su relación para evitar errores de base de datos en la vista
    $inspeccion->load('ordenServicio.vehiculo.cliente', 'ordenServicio.taller');

    return view('impresion.inspeccion', compact('inspeccion'));
})->name('inspeccion.imprimir')->middleware('auth');



Route::get('/cotizacion/{cotizacion}/imprimir', function (App\Models\Cotizacion $cotizacion) {
    // Cargamos la cotización con sus items, vehículo, cliente y taller
    $cotizacion->load('items', 'ordenServicio.vehiculo.cliente', 'ordenServicio.taller');

    return view('impresion.cotizacion', compact('cotizacion'));
})->name('cotizacion.imprimir')->middleware('auth');


Route::get('/status/{token}', function ($token) {
    $orden = \App\Models\OrdenServicio::where('token_url', $token)
        ->with([
            'vehiculo.cliente',
            'taller',
            'cotizaciones' => function($query) {
                $query->whereIn('estatus', ['Enviada', 'Aprobada']);
            },
            // Cargamos el historial de tooooodas las órdenes previas de ese auto
            'vehiculo.ordenesServicio' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->firstOrFail();

    if (is_null($orden->visto_por_cliente_at)) {
        $orden->visto_por_cliente_at = now();
        $orden->save();
    }

    return view('portal.status', compact('orden'));
})->name('portal.status');


// Ruta para ver/imprimir la Orden de Ingreso
Route::get('/status/{token}/orden-pdf', function ($token) {
    $orden = \App\Models\OrdenServicio::where('token_url', $token)
        ->with('vehiculo.cliente', 'taller')
        ->firstOrFail();

    // Retornamos la vista directa al navegador (con soporte total de Flexbox y Grid)
    return view('pdf.orden', compact('orden'));
})->name('portal.orden.pdf');

// Ruta para ver/imprimir una Cotización específica
Route::get('/status/{token}/cotizacion-pdf/{id}', function ($token, $id) {
    $orden = \App\Models\OrdenServicio::where('token_url', $token)->firstOrFail();
    $cotizacion = $orden->cotizaciones()->where('id', $id)->firstOrFail();

    return view('pdf.cotizacion', compact('orden', 'cotizacion'));
})->name('portal.cotizacion.pdf');

// Ruta para ver/imprimir la Hoja de Inspección
Route::get('/status/{token}/inspeccion-pdf', function ($token) {
    $orden = \App\Models\OrdenServicio::where('token_url', $token)->firstOrFail();
    $inspeccion = $orden->inspecciones()->latest()->firstOrFail();

    return view('pdf.inspeccion', compact('inspeccion'));
})->name('portal.inspeccion.pdf');




// RUTA PARA EXPORTAR CORTE DE CAJA (PDF O EXCEL)
Route::get('/finanzas/corte', function (Request $request) {
    $tallerId = auth()->user()->taller_id;
    $inicio = $request->inicio;
    $fin = $request->fin;
    $formato = $request->formato;

    // Obtenemos las transacciones del periodo
    $transacciones = Transaccion::where('taller_id', $tallerId)
        ->whereBetween('fecha', [$inicio, $fin])
        ->orderBy('fecha', 'asc')
        ->get();

    $ingresos = $transacciones->where('tipo', 'Ingreso')->sum('monto');
    $egresos = $transacciones->where('tipo', 'Egreso')->sum('monto');
    $balance = $ingresos - $egresos;

// 1. SI PIDIÓ EXCEL (CSV Nativo)
    if ($formato === 'excel') {
        return response()->streamDownload(function () use ($transacciones) {
            $file = fopen('php://output', 'w');

            // Truco maestro: Agregar BOM UTF-8 para que Excel lea los acentos y "ñ" perfectamente
            fputs($file, "\xEF\xBB\xBF");

            // 1. Añadimos 'Factura' a los encabezados
            fputcsv($file, ['Fecha', 'Tipo', 'Concepto', 'Metodo de Pago', 'Referencia', 'Factura', 'Monto']);

            // Filas
            foreach ($transacciones as $t) {
                // Parseo ultra-seguro
                $fechaSegura = \Carbon\Carbon::parse($t->fecha)->format('Y-m-d');

                fputcsv($file, [
                    $fechaSegura,
                    $t->tipo,
                    $t->concepto,
                    $t->metodo_pago,
                    $t->referencia ?? 'N/A',
                    $t->requiere_factura ? 'SÍ' : 'NO', // <--- 2. Traducimos el booleano a texto
                    $t->monto
                ]);
            }
            fclose($file);
        }, "Corte_Caja_{$inicio}_al_{$fin}.csv");
    }

    // 2. SI PIDIÓ REPORTE PDF PRO
    $taller = \App\Models\Taller::find($tallerId);
    $pdf = Pdf::loadView('portal.corte_pdf', compact('transacciones', 'ingresos', 'egresos', 'balance', 'inicio', 'fin', 'taller'));
    return $pdf->download("Corte_Ejecutivo_{$inicio}.pdf");

})->name('finanzas.corte')->middleware('auth');

// -----------------------------


// RUTA PARA DESCARGAR PDF AL VUELO
Route::get('/factura/{transaccion}/pdf', function(Transaccion $transaccion) {
    if (empty($transaccion->factura_id)) abort(404, 'Factura no encontrada');

    $taller = $transaccion->taller;
    $apiKey = $taller->facturacion_produccion ? $taller->facturapi_key_live : $taller->facturapi_key_test;
    $tenantKey = $taller->facturacion_produccion ? $taller->fiscalapi_tenant_live : $taller->fiscalapi_tenant_test;
    $baseUrl = $taller->facturacion_produccion ? 'https://live.fiscalapi.com' : 'https://test.fiscalapi.com';

    $response = Http::withHeaders(['X-API-KEY' => $apiKey, 'X-TENANT-KEY' => $tenantKey])
        ->post("{$baseUrl}/api/v4/invoices/pdf", ['invoiceId' => $transaccion->factura_id]);

    // --- RAYOS X: Si Fiscal API falla, mostramos el error exacto ---
    if (!$response->successful()) {
        dd('Fallo al pedir PDF a Fiscal API', 'Status HTTP: ' . $response->status(), 'Respuesta:', $response->json(), 'ID que intentaste enviar:', $transaccion->factura_id);
    }

    if($base64 = $response->json('data.base64File')) {
        return response()->streamDownload(function () use ($base64) {
            echo base64_decode($base64);
        }, "Factura_Autonix_{$transaccion->id}.pdf", ['Content-Type' => 'application/pdf']);
    }

    abort(500, 'El servidor de Fiscal API respondió bien pero no devolvió el archivo en Base64.');
})->name('descargar.factura.pdf');

// RUTA PARA DESCARGAR XML AL VUELO
Route::get('/factura/{transaccion}/xml', function(Transaccion $transaccion) {
    if (empty($transaccion->factura_id)) abort(404, 'Factura no encontrada');

    $taller = $transaccion->taller;
    $apiKey = $taller->facturacion_produccion ? $taller->facturapi_key_live : $taller->facturapi_key_test;
    $tenantKey = $taller->facturacion_produccion ? $taller->fiscalapi_tenant_live : $taller->fiscalapi_tenant_test;
    $baseUrl = $taller->facturacion_produccion ? 'https://live.fiscalapi.com' : 'https://test.fiscalapi.com';

    $response = Http::withHeaders(['X-API-KEY' => $apiKey, 'X-TENANT-KEY' => $tenantKey])
        ->get("{$baseUrl}/api/v4/invoices/{$transaccion->factura_id}/xml");

    // --- RAYOS X: Si Fiscal API falla, mostramos el error exacto ---
    if (!$response->successful()) {
        dd('Fallo al pedir XML a Fiscal API', 'Status HTTP: ' . $response->status(), 'Respuesta:', $response->json(), 'ID que intentaste enviar:', $transaccion->factura_id);
    }

    if($base64 = $response->json('data.base64File')) {
        return response()->streamDownload(function () use ($base64) {
            echo base64_decode($base64);
        }, "Factura_Autonix_{$transaccion->id}.xml", ['Content-Type' => 'text/xml']);
    }

    abort(500, 'El servidor de Fiscal API respondió bien pero no devolvió el archivo en Base64.');
})->name('descargar.factura.xml');

//require __DIR__.'/auth.php';
