<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('items');
            $table->decimal('iva', 10, 2)->default(0)->after('subtotal');
            $table->boolean('aplica_iva')->default(false)->after('iva');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'iva', 'aplica_iva']);
        });
    }
};
