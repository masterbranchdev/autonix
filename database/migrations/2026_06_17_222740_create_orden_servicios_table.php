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
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();

            $table->string('folio')->nullable();
            $table->dateTime('fecha_ingreso')->useCurrent();
            $table->dateTime('fecha_salida')->nullable();
            $table->boolean('ingreso_grua')->default(false);

            $table->text('trabajo_a_realizar')->nullable();
            $table->text('observaciones')->nullable();

            // El checklist físico guardado de forma inteligente
            $table->json('inventario')->nullable();
            $table->string('nivel_gasolina')->nullable();

            // Estatus del proceso (Ej: Ingresado, En Revisión, Listo, Entregado)
            $table->string('estatus')->default('Ingresado');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_servicios');
    }
};
