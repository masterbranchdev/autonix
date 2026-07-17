<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Le decimos que la columna existente ahora acepta NULL
            $table->unsignedBigInteger('orden_servicio_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Si nos arrepentimos, la vuelve a hacer obligatoria
            $table->unsignedBigInteger('orden_servicio_id')->nullable(false)->change();
        });
    }
};
