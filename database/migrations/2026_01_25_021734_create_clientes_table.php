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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->comment('Código único del cliente');
            $table->string('nombre', 150);
            $table->string('documento', 20)->nullable();
            $table->string('tipo_documento', 10)->default('CC');
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('direccion', 255);
            $table->string('barrio', 100)->nullable();
            $table->string('municipio', 100)->default('Villamaría');
            $table->string('departamento', 100)->default('Caldas');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->text('referencia_ubicacion')->nullable();
            $table->enum('estado', ['activo', 'suspendido', 'retirado', 'cortado'])->default('activo');
            $table->date('fecha_instalacion')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('cobrador_id')->nullable()->constrained('cobradors')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
