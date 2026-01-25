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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained()->restrictOnDelete();
            $table->integer('mes')->comment('Mes de facturación (1-12)');
            $table->integer('anio')->comment('Año de facturación');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('recargo', 12, 2)->default(0)->comment('Recargo por mora');
            $table->decimal('total', 12, 2);
            $table->decimal('saldo', 12, 2)->comment('Saldo pendiente');
            $table->enum('estado', ['pendiente', 'pagada', 'parcial', 'vencida', 'anulada'])->default('pendiente');
            $table->text('concepto')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['servicio_id', 'mes', 'anio'], 'factura_periodo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
