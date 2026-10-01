<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->decimal('descuento', 12, 2)->default(0)->after('monto');
            $table->text('justificacion_descuento')->nullable()->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['descuento', 'justificacion_descuento']);
        });
    }
};
