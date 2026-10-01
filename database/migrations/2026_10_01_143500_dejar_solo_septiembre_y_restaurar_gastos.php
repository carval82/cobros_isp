<?php

use App\Models\Factura;
use App\Models\GastoProyecto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Deja visibles solo las facturas de septiembre 2026 y recupera
     * gastos que hubieran quedado ocultos. La migración anterior puede
     * volver a mostrar meses viejos; esta los oculta de nuevo.
     */
    public function up(): void
    {
        $gastos = GastoProyecto::onlyTrashed()->restore();

        $ocultadas = Factura::query()
            ->where(function ($q) {
                $q->where('mes', '!=', 9)->orWhere('anio', '!=', 2026);
            })
            ->delete();

        $septiembre = Factura::where('mes', 9)->where('anio', 2026)->count();

        $resumen = "Gastos restaurados: {$gastos}. Facturas de otros meses ocultadas: {$ocultadas}. Facturas de septiembre visibles: {$septiembre}.";

        Log::info($resumen);
        echo $resumen.PHP_EOL;
    }

    public function down(): void
    {
        //
    }
};
