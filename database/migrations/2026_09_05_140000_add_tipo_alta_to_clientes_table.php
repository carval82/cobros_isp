<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo_alta', ['nuevo', 'antiguo'])->default('antiguo')->after('estado');
            $table->unsignedTinyInteger('primer_mes_facturable')->nullable()->after('tipo_alta');
            $table->unsignedSmallInteger('primer_anio_facturable')->nullable()->after('primer_mes_facturable');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['tipo_alta', 'primer_mes_facturable', 'primer_anio_facturable']);
        });
    }
};
