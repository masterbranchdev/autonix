<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

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

// -----------------------------

require __DIR__.'/auth.php';
