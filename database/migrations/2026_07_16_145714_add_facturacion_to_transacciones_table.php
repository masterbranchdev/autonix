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
        Schema::table('transacciones', function (Blueprint $table) {
            //
            $table->string('factura_id')->nullable()->after('diagnostico_ia'); // El ID que nos devuelva Facturapi
            $table->string('estado_factura')->default('No Facturado')->after('factura_id'); // No Facturado, Timbrada, Cancelada
            $table->string('url_pdf_factura')->nullable()->after('estado_factura');
            $table->string('url_xml_factura')->nullable()->after('url_pdf_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            //
        });
    }
};
