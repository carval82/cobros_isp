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
        Schema::create('liquidacions', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('cobrador_id')->constrained('cobradors')->restrictOnDelete();
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->date('fecha_liquidacion');
            $table->decimal('total_recaudado', 12, 2)->default(0);
            $table->decimal('total_comision', 12, 2)->default(0);
            $table->decimal('total_a_entregar', 12, 2)->default(0)->comment('Total recaudado - comisión');
            $table->integer('cantidad_cobros')->default(0);
            $table->integer('cantidad_pagos')->default(0);
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacions');
    }
};
