<?php

use App\Models\Cliente;
use App\Services\FacturacionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $servicio = app(FacturacionService::class);

        Cliente::where('estado', 'retirado')->each(function (Cliente $cliente) use ($servicio) {
            $servicio->cerrarCuentasPorRetiro($cliente);
        });
    }

    public function down(): void
    {
        //
    }
};
