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
            $table->json('testigos')->nullable(); // Guardará los íconos del tablero
            $table->json('danios_carroceria')->nullable(); // Guardará los 4 lados del auto
            $table->longText('firma')->nullable(); // Guardará la firma digital en texto Base64
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
