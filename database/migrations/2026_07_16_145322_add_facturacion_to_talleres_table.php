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
        Schema::table('talleres', function (Blueprint $table) {
            $table->string('facturapi_key_test')->nullable()->after('openai_api_key');
            $table->string('facturapi_key_live')->nullable()->after('facturapi_key_test');
            $table->boolean('facturacion_produccion')->default(false)->after('facturapi_key_live');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            //
        });
    }
};
