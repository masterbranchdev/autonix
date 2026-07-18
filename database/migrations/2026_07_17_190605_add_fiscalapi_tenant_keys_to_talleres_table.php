<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            $table->string('fiscalapi_tenant_test')->nullable()->after('facturapi_key_live');
            $table->string('fiscalapi_tenant_live')->nullable()->after('fiscalapi_tenant_test');
        });
    }

    public function down(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            $table->dropColumn(['fiscalapi_tenant_test', 'fiscalapi_tenant_live']);
        });
    }
};
