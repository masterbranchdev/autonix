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
        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('plan')->default('prueba'); // ej. prueba, mensual, anual
            $table->boolean('activo')->default(true); // Botón de apagado si dejan de pagar
            $table->timestamp('fecha_suscripcion')->useCurrent(); // Tiempo inscritos
            $table->date('vencimiento_suscripcion')->nullable();

            // --- CONFIGURACIÓN DE WHATSAPP (TWILIO SAAS) ---
            $table->string('twilio_sid')->nullable();
            $table->string('twilio_token')->nullable();
            $table->string('twilio_whatsapp')->nullable(); // El número asignado a este taller

            // En lugar de interfón, guardamos los IDs de las plantillas de Autonix
            $table->string('twilio_tpl_estatus')->nullable();       // Ej. "Tu auto está en revisión..."
            $table->string('twilio_tpl_cotizacion')->nullable();    // Ej. "Tienes una nueva cotización por aprobar"
            $table->string('twilio_tpl_recordatorio')->nullable();  // Ej. "Hace 6 meses nos visitaste..."

            // --- DATOS PÚBLICOS DEL TALLER (Para PDFs y Cotizaciones) ---
            $table->string('logo_path')->nullable(); // Aquí guardaremos la ruta de la imagen cuando la suban
            $table->string('telefono')->nullable();
            $table->string('whatsapp_publico')->nullable(); // El que verá el cliente impreso, independiente del de Twilio
            $table->text('domicilio')->nullable();

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talleres');
    }
};
