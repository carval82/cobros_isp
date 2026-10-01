<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AtribucionPago
{
    public const DIAS_GRACIA = 10;

    public static function sqlPagadoEnPeriodoFactura(): string
    {
        $dias = self::DIAS_GRACIA - 1;

        return '(select coalesce(sum(pagos.monto), 0) from pagos'
            .' where pagos.factura_id = facturas.id'
            .' and pagos.deleted_at is null'
            .' and pagos.fecha_pago >= str_to_date(concat(facturas.anio, "-", lpad(facturas.mes, 2, "0"), "-01"), "%Y-%m-%d")'
            ." and pagos.fecha_pago <= date_add(date_add(str_to_date(concat(facturas.anio, \"-\", lpad(facturas.mes, 2, \"0\"), \"-01\"), \"%Y-%m-%d\"), interval 1 month), interval {$dias} day))";
    }

    public static function aplicar(Builder $query, int $mes, int $anio): Builder
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $finGracia = Carbon::create($anio, $mes, 1)->addMonth()->addDays(self::DIAS_GRACIA - 1)->toDateString();
        $anterior = Carbon::create($anio, $mes, 1)->subMonth();

        return $query->where(function ($q) use ($mes, $anio, $inicio, $finGracia, $anterior) {
            $q->where(function ($enFactura) use ($mes, $anio, $inicio, $finGracia) {
                $enFactura->whereBetween('fecha_pago', [$inicio, $finGracia])
                    ->whereHas('factura', function ($factura) use ($mes, $anio) {
                        $factura->where('mes', $mes)->where('anio', $anio);
                    });
            })->orWhere(function ($enCalendario) use ($mes, $anio, $anterior) {
                $enCalendario->whereMonth('fecha_pago', $mes)
                    ->whereYear('fecha_pago', $anio)
                    ->whereDoesntHave('factura', function ($factura) use ($mes, $anio, $anterior) {
                        $factura->where(function ($coincide) use ($mes, $anio, $anterior) {
                            $coincide->where(function ($mismoMes) use ($mes, $anio) {
                                $mismoMes->where('mes', $mes)->where('anio', $anio);
                            })->orWhere(function ($gracia) use ($anterior) {
                                $gracia->where('mes', $anterior->month)
                                    ->where('anio', $anterior->year)
                                    ->whereRaw('day(pagos.fecha_pago) <= ?', [self::DIAS_GRACIA]);
                            });
                        });
                    });
            });
        });
    }

    public static function limiteGracia(int $mes, int $anio): Carbon
    {
        return Carbon::create($anio, $mes, 1)->addMonth()->addDays(self::DIAS_GRACIA - 1)->endOfDay();
    }

    public static function periodoLiquidacion(Carbon $fechaPago, int $facturaMes, int $facturaAnio): array
    {
        $inicio = Carbon::create($facturaAnio, $facturaMes, 1)->startOfDay();
        $limite = self::limiteGracia($facturaMes, $facturaAnio);

        if ($fechaPago->gte($inicio) && $fechaPago->lte($limite)) {
            return [$facturaMes, $facturaAnio];
        }

        return [(int) $fechaPago->month, (int) $fechaPago->year];
    }
}
