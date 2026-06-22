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
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->id();

            // Relaciones sin bloqueo (nullOnDelete)
            $table->foreignId('taller_id')->nullable()->constrained('talleres')->nullOnDelete();
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();

            $table->date('proximo_servicio_fecha')->nullable();
            $table->integer('proximo_servicio_km')->nullable();

            // --- LOS SEMÁFOROS (Guardados en JSON para no crear 50 columnas) ---
            $table->json('check_fluidos')->nullable(); // Transmisión, frenos, anticongelante...
            $table->json('check_mecanico')->nullable(); // Fugas, bandas, suspensión...
            $table->json('check_llantas_frenos')->nullable(); // Presión, vida de llantas y balatas
            $table->json('check_bateria')->nullable(); // Bien/Mal, Amperaje

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspeccions');
    }
};
