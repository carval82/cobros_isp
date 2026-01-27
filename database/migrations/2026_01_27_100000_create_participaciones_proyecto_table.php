<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participaciones_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->string('socio_nombre');
            $table->string('socio_documento')->nullable();
            $table->string('socio_telefono')->nullable();
            $table->decimal('porcentaje', 5, 2); // Ej: 50.00, 40.00, 20.00
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participaciones_proyecto');
    }
};
