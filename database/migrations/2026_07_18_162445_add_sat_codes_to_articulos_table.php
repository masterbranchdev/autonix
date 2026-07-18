<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            // Agregamos las columnas y las hacemos nullable para que no rompan los registros que ya tienes
            $table->string('clave_sat', 20)->nullable()->after('nombre');
            $table->string('unidad_sat', 10)->nullable()->after('clave_sat');
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn(['clave_sat', 'unidad_sat']);
        });
    }
};
