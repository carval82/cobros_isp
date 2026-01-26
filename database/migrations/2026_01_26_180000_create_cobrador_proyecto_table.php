<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrador_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobrador_id')->constrained('cobradors')->onDelete('cascade');
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->decimal('comision_porcentaje', 5, 2)->default(0)->comment('Comisión específica para este proyecto');
            $table->timestamps();
            
            $table->unique(['cobrador_id', 'proyecto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrador_proyecto');
    }
};
