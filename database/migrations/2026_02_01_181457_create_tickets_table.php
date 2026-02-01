<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('proyecto_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('tipo', ['daño', 'cobro', 'soporte', 'otro'])->default('soporte');
            $table->string('asunto');
            $table->text('descripcion');
            $table->enum('estado', ['abierto', 'en_proceso', 'resuelto', 'cerrado'])->default('abierto');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->text('respuesta')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_respuesta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
