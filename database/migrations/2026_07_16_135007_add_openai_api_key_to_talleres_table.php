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
            $table->string('openai_api_key')->nullable()->after('whatsapp_publico');
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
