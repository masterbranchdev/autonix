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
        Schema::table('inspecciones', function (Blueprint $table) {
            $table->json('check_puntos')->nullable();
            $table->text('observaciones_puntos')->nullable();
            $table->json('llantas')->nullable();
            $table->json('balatas')->nullable();
            $table->json('bateria')->nullable();
            $table->json('adicionales')->nullable();
            $table->longText('firma')->nullable(); // Campo para guardar la firma digital
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspecciones', function (Blueprint $table) {
            //
        });
    }
};
