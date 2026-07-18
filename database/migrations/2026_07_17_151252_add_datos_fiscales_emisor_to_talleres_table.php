<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->after('telefono');
            $table->string('razon_social')->nullable()->after('rfc');
            $table->string('codigo_postal', 5)->nullable()->after('razon_social');
            $table->string('regimen_fiscal')->nullable()->after('codigo_postal');
        });
    }

    public function down(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            $table->dropColumn(['rfc', 'razon_social', 'codigo_postal', 'regimen_fiscal']);
        });
    }
};
