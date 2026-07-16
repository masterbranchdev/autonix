<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taller_id')->constrained('talleres')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('orden_servicio_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();

            $table->date('fecha_programada');
            $table->string('motivo');
            $table->string('nivel_importancia')->default('Media');
            $table->text('notas_internas')->nullable();

            $table->enum('estatus', ['Pendiente', 'Contactado', 'Cita Agendada', 'Completado', 'Cancelado'])->default('Pendiente');

            // --- TUS NUEVOS CAMPOS DE SEGUIMIENTO ---
            $table->dateTime('fecha_hora_cita')->nullable(); // Para cuando el estatus es "Cita Agendada"
            $table->text('observaciones_seguimiento')->nullable(); // Notas de qué dijo al contactarlo o por qué canceló
            // ----------------------------------------

            $table->boolean('enviar_whatsapp_automatico')->default(true);
            $table->timestamp('whatsapp_enviado_at')->nullable();
            $table->string('whatsapp_job_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
