<?php

namespace App\Providers;

use App\Models\Compra;
use App\Observers\CompraObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Cotizacion;
use App\Observers\CotizacionObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Cotizacion::observe(CotizacionObserver::class);
        Compra::observe(CompraObserver::class);
    }
}
