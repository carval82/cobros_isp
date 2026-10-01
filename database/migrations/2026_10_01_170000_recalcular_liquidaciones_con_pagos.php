<?php

use App\Models\Liquidacion;
use App\Services\CobradorInformeService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $servicio = app(CobradorInformeService::class);

        Liquidacion::where('estado', '!=', 'anulada')->each(function (Liquidacion $liquidacion) use ($servicio) {
            $servicio->recalcular($liquidacion);
        });
    }

    public function down(): void
    {
        //
    }
};
