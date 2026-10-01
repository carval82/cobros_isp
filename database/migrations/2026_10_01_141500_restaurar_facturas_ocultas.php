<?php

use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Las facturas de la limpieza de septiembre quedaron con deleted_at.
     * Esta migración las vuelve a dejar visibles. Si septiembre se generó
     * de nuevo para el mismo servicio, se quita esa copia vacía y vuelve
     * la factura original. Los pagos borrados con forceDelete ya no están
     * en la tabla y no se pueden reconstruir aquí.
     */
    public function up(): void
    {
        $restauradas = 0;
        $copiasVaciasQuitadas = 0;
        $dejadasOcultas = 0;

        Factura::onlyTrashed()->orderBy('id')->each(function (Factura $factura) use (&$restauradas, &$copiasVaciasQuitadas, &$dejadasOcultas) {
            $activa = Factura::query()
                ->where('servicio_id', $factura->servicio_id)
                ->where('mes', $factura->mes)
                ->where('anio', $factura->anio)
                ->first();

            if ($activa) {
                $tienePagos = DB::table('pagos')
                    ->where('factura_id', $activa->id)
                    ->exists();

                if ($tienePagos) {
                    $dejadasOcultas++;

                    return;
                }

                $activa->forceDelete();
                $copiasVaciasQuitadas++;
            }

            $factura->restore();
            $restauradas++;
        });

        $pagos = Pago::onlyTrashed()->restore();

        $resumen = "Facturas restauradas: {$restauradas}. Copias vacias quitadas: {$copiasVaciasQuitadas}. Dejadas ocultas por tener pagos nuevos: {$dejadasOcultas}. Pagos restaurados: {$pagos}.";

        Log::info($resumen);
        echo $resumen.PHP_EOL;
    }

    public function down(): void
    {
        //
    }
};
