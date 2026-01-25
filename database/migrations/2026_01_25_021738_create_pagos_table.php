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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recibo', 20)->unique();
            $table->foreignId('factura_id')->constrained()->restrictOnDelete();
            $table->foreignId('cobrador_id')->nullable()->constrained('cobradors')->nullOnDelete();
            $table->foreignId('cobro_id')->nullable()->constrained()->nullOnDelete();
            $table->date('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'nequi', 'daviplata', 'tarjeta', 'otro'])->default('efectivo');
            $table->string('referencia_pago', 100)->nullable()->comment('Número de transferencia o referencia');
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
