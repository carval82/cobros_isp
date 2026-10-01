<?php

namespace App\Services;

use App\Models\Cobrador;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Factura;
use App\Models\Liquidacion;
use App\Models\Pago;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CobradorInformeService
{
    public static function meses(): array
    {
        return LiquidacionProyectoService::meses();
    }

    public function informeMensual(int $mes, int $anio): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfDay();
        $hasta = $desde->copy()->endOfMonth();

        $filas = Cobrador::withCount([
            'clientes as clientes_asignados' => function ($q) {
                $q->where('estado', '!=', 'retirado');
            },
        ])
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Cobrador $cobrador) => $this->filaCobrador($cobrador, $mes, $anio, $desde, $hasta));

        return [
            'mes' => $mes,
            'anio' => $anio,
            'periodo' => ($this->meses()[$mes] ?? $mes) . ' ' . $anio,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'totales' => [
                'clientes' => $filas->sum('clientes'),
                'proyectado' => $filas->sum('proyectado'),
                'recaudado' => $filas->sum('recaudado'),
                'pendiente' => $filas->sum('pendiente'),
                'comision' => $filas->sum('comision'),
                'a_entregar' => $filas->sum('a_entregar'),
            ],
            'cobradores' => $filas->values(),
        ];
    }

    public function detalleCobrador(Cobrador $cobrador, int $mes, int $anio): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfDay();
        $hasta = $desde->copy()->endOfMonth();
        $fila = $this->filaCobrador($cobrador, $mes, $anio, $desde, $hasta);

        $clientes = $cobrador->clientes()
            ->with('proyecto')
            ->where('estado', '!=', 'retirado')
            ->orderBy('nombre')
            ->get()
            ->map(function ($cliente) use ($mes, $anio) {
                $facturas = Factura::where('cliente_id', $cliente->id)
                    ->where('mes', $mes)
                    ->where('anio', $anio)
                    ->get();
                $cortes = $this->cortesDelMes($facturas, $mes, $anio);

                return [
                    'id' => $cliente->id,
                    'codigo' => $cliente->codigo,
                    'nombre' => $cliente->nombre,
                    'documento' => $cliente->documento,
                    'estado' => $cliente->estado,
                    'proyecto_id' => $cliente->proyecto_id,
                    'proyecto' => $cliente->proyecto?->nombre ?? 'Sin proyecto',
                    'proyecto_color' => $cliente->proyecto?->color ?? '#64748b',
                    'proyectado' => (float) $facturas->sum('total'),
                    'pendiente' => $cortes->sum('pendiente_mes'),
                    'recaudado' => $cortes->sum('pagado_mes'),
                    'pagado_despues' => $cortes->sum('pagado_despues'),
                    'facturas' => $cortes->map(fn ($f) => [
                        'numero' => $f['numero'],
                        'total' => $f['total'],
                        'saldo' => $f['pendiente_mes'],
                        'pagado_mes' => $f['pagado_mes'],
                        'pagado_despues' => $f['pagado_despues'],
                        'estado' => $f['estado_mes'],
                    ]),
                ];
            });

        return [
            'cobrador' => $fila,
            'clientes' => $clientes,
            'proyectos' => $fila['proyectos'],
            'periodo' => ($this->meses()[$mes] ?? $mes) . ' ' . $anio,
            'mes' => $mes,
            'anio' => $anio,
        ];
    }

    public function generarLiquidacion(Cobrador $cobrador, int $mes, int $anio, ?int $userId = null): Liquidacion
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfDay();
        $hasta = $desde->copy()->endOfMonth();

        $existente = Liquidacion::where('cobrador_id', $cobrador->id)
            ->whereDate('fecha_desde', $desde->toDateString())
            ->whereDate('fecha_hasta', $hasta->toDateString())
            ->where('estado', '!=', 'anulada')
            ->first();

        if ($existente) {
            return $existente;
        }

        return DB::transaction(function () use ($cobrador, $desde, $hasta, $userId) {
            Cobro::where('cobrador_id', $cobrador->id)
                ->where('estado', 'abierto')
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                ->get()
                ->each(fn (Cobro $cobro) => $cobro->cerrar());

            $cobros = Cobro::where('cobrador_id', $cobrador->id)
                ->whereIn('estado', ['cerrado'])
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                ->whereNull('liquidacion_id')
                ->get();

            $pagosPeriodo = AtribucionPago::aplicar(
                Pago::where('cobrador_id', $cobrador->id),
                (int) $desde->month,
                (int) $desde->year
            );
            $totalRecaudado = (float) (clone $pagosPeriodo)->sum('monto');
            $totalComision = round($totalRecaudado * ((float) $cobrador->comision_porcentaje / 100), 2);
            $cantidadPagos = (int) (clone $pagosPeriodo)->count();

            $liquidacion = Liquidacion::create([
                'cobrador_id' => $cobrador->id,
                'fecha_desde' => $desde->toDateString(),
                'fecha_hasta' => $hasta->toDateString(),
                'fecha_liquidacion' => now(),
                'total_recaudado' => $totalRecaudado,
                'total_comision' => $totalComision,
                'total_a_entregar' => $totalRecaudado - $totalComision,
                'cantidad_cobros' => $cobros->count(),
                'cantidad_pagos' => $cantidadPagos,
                'observaciones' => 'Liquidación mensual automática según cartera asignada y recaudo del período.',
                'user_id' => $userId,
            ]);

            foreach ($cobros as $cobro) {
                $cobro->update([
                    'liquidacion_id' => $liquidacion->id,
                    'estado' => 'liquidado',
                ]);
            }

            return $liquidacion;
        });
    }

    public function recalcular(Liquidacion $liquidacion): Liquidacion
    {
        $cobrador = $liquidacion->cobrador;
        $pagosPeriodo = AtribucionPago::aplicar(
            Pago::where('cobrador_id', $liquidacion->cobrador_id),
            (int) $liquidacion->fecha_desde->month,
            (int) $liquidacion->fecha_desde->year
        );

        $totalRecaudado = (float) (clone $pagosPeriodo)->sum('monto');
        $totalComision = round($totalRecaudado * ((float) $cobrador->comision_porcentaje / 100), 2);
        $cantidadPagos = (int) (clone $pagosPeriodo)->count();

        $liquidacion->update([
            'total_recaudado' => $totalRecaudado,
            'total_comision' => $totalComision,
            'total_a_entregar' => $totalRecaudado - $totalComision,
            'cantidad_pagos' => $cantidadPagos,
        ]);

        return $liquidacion->fresh();
    }

    private function filaCobrador(Cobrador $cobrador, int $mes, int $anio, Carbon $desde, Carbon $hasta): array
    {
        $clientes = $cobrador->clientes()
            ->where('estado', '!=', 'retirado')
            ->get(['id', 'proyecto_id']);

        $clienteIds = $clientes->pluck('id');

        $facturas = Factura::whereIn('cliente_id', $clienteIds)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get(['id', 'cliente_id', 'numero', 'total', 'saldo', 'estado', 'mes', 'anio']);

        $cortes = $this->cortesDelMes($facturas, $mes, $anio)->keyBy('id');

        $pagos = AtribucionPago::aplicar(
            Pago::where('cobrador_id', $cobrador->id)->with(['factura:id,cliente_id']),
            $mes,
            $anio
        )->get(['id', 'factura_id', 'monto']);

        $proyectos = Proyecto::whereIn('id', $clientes->pluck('proyecto_id')->filter()->unique())
            ->get(['id', 'nombre', 'color'])
            ->keyBy('id');

        $proyectosFila = $clientes
            ->groupBy(fn (Cliente $cliente) => $cliente->proyecto_id ?: 0)
            ->map(function ($grupo, $proyectoId) use ($facturas, $pagos, $proyectos) {
                $ids = $grupo->pluck('id');
                $facts = $facturas->whereIn('cliente_id', $ids);
                $cortesGrupo = $cortes->whereIn('id', $facts->pluck('id'));
                $recs = $pagos->filter(fn (Pago $pago) => $ids->contains($pago->factura?->cliente_id));
                $proyecto = $proyectos->get((int) $proyectoId);

                return [
                    'id' => $proyectoId ? (int) $proyectoId : null,
                    'nombre' => $proyecto?->nombre ?? 'Sin proyecto',
                    'color' => $proyecto?->color ?? '#64748b',
                    'clientes' => $grupo->count(),
                    'proyectado' => (float) $facts->sum('total'),
                    'pendiente' => (float) $cortesGrupo->sum('pendiente_mes'),
                    'recaudado' => (float) $recs->sum('monto'),
                ];
            })
            ->sortBy('nombre')
            ->values();

        $proyectado = (float) $facturas->sum('total');
        $pendiente = (float) $cortes->sum('pendiente_mes');
        $recaudadoCartera = (float) $cortes->sum('pagado_mes');
        $recaudado = (float) $pagos->sum('monto');
        $comision = round($recaudado * ((float) $cobrador->comision_porcentaje / 100), 2);
        $cumplimiento = $proyectado > 0 ? round(($recaudadoCartera / $proyectado) * 100, 1) : 0;

        $liquidacion = Liquidacion::where('cobrador_id', $cobrador->id)
            ->whereDate('fecha_desde', $desde->toDateString())
            ->whereDate('fecha_hasta', $hasta->toDateString())
            ->where('estado', '!=', 'anulada')
            ->first();

        return [
            'id' => $cobrador->id,
            'nombre' => $cobrador->nombre,
            'documento' => $cobrador->documento,
            'comision_porcentaje' => (float) $cobrador->comision_porcentaje,
            'clientes' => $clientes->count(),
            'proyectado' => $proyectado,
            'recaudado' => $recaudado,
            'recaudado_cartera' => $recaudadoCartera,
            'pendiente' => $pendiente,
            'cumplimiento' => $cumplimiento,
            'comision' => $comision,
            'a_entregar' => $recaudado - $comision,
            'liquidacion_id' => $liquidacion?->id,
            'liquidacion_estado' => $liquidacion?->estado,
            'proyectos' => $proyectosFila,
        ];
    }

    private function cortesDelMes($facturas, int $mes, int $anio)
    {
        $pagos = $facturas->isEmpty()
            ? collect()
            : Pago::whereIn('factura_id', $facturas->pluck('id'))->get(['factura_id', 'monto', 'fecha_pago']);

        return $facturas->map(function (Factura $factura) use ($pagos) {
            $inicio = Carbon::create((int) $factura->anio, (int) $factura->mes, 1)->startOfDay();
            $limite = AtribucionPago::limiteGracia((int) $factura->mes, (int) $factura->anio);
            $delMes = $pagos->filter(function (Pago $pago) use ($factura, $inicio, $limite) {
                return (int) $pago->factura_id === (int) $factura->id
                    && $pago->fecha_pago->gte($inicio)
                    && $pago->fecha_pago->lte($limite);
            });
            $despues = $pagos->filter(function (Pago $pago) use ($factura, $limite) {
                return (int) $pago->factura_id === (int) $factura->id
                    && $pago->fecha_pago->gt($limite);
            });

            $pagadoMes = (float) $delMes->sum('monto');
            $pagadoDespues = (float) $despues->sum('monto');
            $pendienteMes = max(0, (float) $factura->total - $pagadoMes);

            if ($pagadoMes <= 0 && $pagadoDespues > 0) {
                $estado = 'parcial';
            } elseif ($pagadoMes <= 0) {
                $estado = $factura->estado === 'anulada' ? 'anulada' : 'pendiente';
            } elseif ($pendienteMes <= 0.5) {
                $estado = 'pagada';
            } else {
                $estado = 'parcial';
            }

            return [
                'id' => $factura->id,
                'numero' => $factura->numero,
                'total' => (float) $factura->total,
                'pagado_mes' => $pagadoMes,
                'pagado_despues' => $pagadoDespues,
                'pendiente_mes' => $pendienteMes,
                'estado_mes' => $estado,
            ];
        });
    }
}
