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
            $table->integer('limite_ia_mensual')->default(100)->after('openai_api_key');
            $table->integer('consumo_ia_mes')->default(0)->after('limite_ia_mensual');
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
