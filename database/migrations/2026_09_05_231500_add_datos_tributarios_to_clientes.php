<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('dv', 1)->nullable()->after('documento');
            $table->string('tipo_persona', 20)->default('natural')->after('tipo_documento');
            $table->string('regimen', 20)->default('simplificado')->after('tipo_persona');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['dv', 'tipo_persona', 'regimen']);
        });
    }
};
