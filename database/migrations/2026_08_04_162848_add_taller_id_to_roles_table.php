<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // 1. Agregamos el ID del taller
            $table->unsignedBigInteger('taller_id')->nullable()->after('id');

            // 2. Borramos la regla antigua que impedía nombres repetidos a nivel global
            $table->dropUnique('roles_name_guard_name_unique');

            // 3. Creamos la nueva regla: No se puede repetir el nombre DENTRO del mismo taller
            $table->unique(['taller_id', 'name', 'guard_name'], 'roles_taller_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_taller_name_unique');
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            $table->dropColumn('taller_id');
        });
    }
};
