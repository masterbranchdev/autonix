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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->after('email');
            $table->string('razon_social')->nullable()->after('rfc');
            $table->string('codigo_postal', 5)->nullable()->after('razon_social');
            $table->string('regimen_fiscal')->nullable()->after('codigo_postal'); // Ej. 601, 612
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            //
        });
    }
};
