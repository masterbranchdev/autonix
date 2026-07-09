<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\OrdenServicio;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Corregimos el nombre a 'orden_servicios'
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('token_url', 64)->nullable()->after('estatus');
            $table->timestamp('visto_por_cliente_at')->nullable()->after('token_url');
        });

        $ordenes = OrdenServicio::whereNull('token_url')->get();
        foreach ($ordenes as $orden) {
            $orden->token_url = Str::random(32);
            $orden->save();
        }

        // Corregimos aquí también
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->unique('token_url');
        });
    }

    public function down(): void
    {
        // Y aquí
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropUnique(['token_url']);
            $table->dropColumn(['token_url', 'visto_por_cliente_at']);
        });
    }
};
