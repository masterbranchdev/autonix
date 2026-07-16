<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->integer('consultas_ia_realizadas')->default(0)->after('diagnostico_ia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            //
        });
    }
};
