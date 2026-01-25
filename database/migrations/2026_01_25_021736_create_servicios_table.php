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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_servicio_id')->constrained()->restrictOnDelete();
            $table->string('ip_asignada', 45)->nullable();
            $table->string('mac_address', 20)->nullable();
            $table->string('equipo_modelo', 100)->nullable();
            $table->string('equipo_serial', 100)->nullable();
            $table->integer('dia_corte')->default(1)->comment('Día del mes para facturación');
            $table->integer('dia_pago_limite')->default(10)->comment('Día límite de pago');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('precio_especial', 12, 2)->nullable()->comment('Precio diferente al plan si aplica');
            $table->enum('estado', ['activo', 'suspendido', 'cancelado', 'cortado'])->default('activo');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
