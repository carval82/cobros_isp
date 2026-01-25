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
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobrador_id')->constrained('cobradors')->restrictOnDelete();
            $table->date('fecha');
            $table->enum('estado', ['abierto', 'cerrado', 'liquidado'])->default('abierto');
            $table->decimal('total_recaudado', 12, 2)->default(0);
            $table->decimal('total_comision', 12, 2)->default(0);
            $table->integer('cantidad_pagos')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->foreignId('liquidacion_id')->nullable()->constrained('liquidacions')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
