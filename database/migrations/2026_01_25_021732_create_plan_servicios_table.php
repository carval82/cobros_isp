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
        Schema::create('plan_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->integer('velocidad_bajada')->comment('Mbps de bajada');
            $table->integer('velocidad_subida')->comment('Mbps de subida');
            $table->decimal('precio', 12, 2);
            $table->enum('tipo', ['residencial', 'comercial', 'empresarial'])->default('residencial');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_servicios');
    }
};
