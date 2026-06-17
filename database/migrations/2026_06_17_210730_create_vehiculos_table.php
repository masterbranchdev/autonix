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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres')->cascadeOnDelete(); // Vital para aislar datos
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();

            $table->string('vin', 17)->nullable()->unique();
            $table->string('placas')->nullable();
            $table->string('marca');
            $table->string('modelo');
            $table->integer('anio');
            $table->string('color')->nullable();
            $table->integer('kilometraje')->default(0);
            $table->string('tarjeta_circulacion')->nullable();
            $table->string('poliza_seguro')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
