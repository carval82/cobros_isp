<?php

use App\Models\Liquidacion;
use App\Models\Pago;
use App\Services\CobradorInformeService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Pago::with([
            'factura' => fn ($q) => $q->withTrashed(),
            'cobro',
        ])->where('notas', 'like', 'Pago masivo%')->each(function (Pago $pago) {
            $factura = $pago->factura;
            if (! $factura || (int) $factura->mes < 1 || (int) $factura->mes > 12) {
                return;
            }

            $inicio = Carbon::create((int) $factura->anio, (int) $factura->mes, 1)->startOfDay();
            $fin = $inicio->copy()->endOfMonth()->startOfDay();
            $fecha = $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->startOfDay() : null;
            $cobro = $pago->cobro;
            $cobroFuera = $cobro && (! $cobro->fecha || ! Carbon::parse($cobro->fecha)->betweenIncluded($inicio, $fin));
            $fechaFuera = ! $fecha || $fecha->lt($inicio) || $fecha->gt($fin);

            if (! $fechaFuera && ! $cobroFuera) {
                return;
            }

            if ($fechaFuera) {
                $pago->fecha_pago = $fin->toDateString();
            }
            if ($cobroFuera) {
                $pago->cobro_id = null;
            }
            $pago->save();

            if ($cobroFuera) {
                $cobro->recalcularTotales();
            }
        });

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
