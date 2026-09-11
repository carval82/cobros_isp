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
                    'pendiente' => (float) $facturas->sum('saldo'),
                    'recaudado' => (float) $facturas->sum(fn ($f) => $f->total - $f->saldo),
                    'facturas' => $facturas->map(fn ($f) => [
                        'numero' => $f->numero,
                        'total' => (float) $f->total,
                        'saldo' => (float) $f->saldo,
                        'estado' => $f->estado,
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

            if ($cobros->isNotEmpty()) {
                $totalRecaudado = (float) $cobros->sum('total_recaudado');
                $totalComision = (float) $cobros->sum('total_comision');
                $cantidadPagos = (int) $cobros->sum('cantidad_pagos');
            } else {
                $totalRecaudado = (float) Pago::where('cobrador_id', $cobrador->id)
                    ->whereBetween('fecha_pago', [$desde->toDateString(), $hasta->toDateString()])
                    ->sum('monto');
                $totalComision = round($totalRecaudado * ((float) $cobrador->comision_porcentaje / 100), 2);
                $cantidadPagos = (int) Pago::where('cobrador_id', $cobrador->id)
                    ->whereBetween('fecha_pago', [$desde->toDateString(), $hasta->toDateString()])
                    ->count();
            }

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

    private function filaCobrador(Cobrador $cobrador, int $mes, int $anio, Carbon $desde, Carbon $hasta): array
    {
        $clientes = $cobrador->clientes()
            ->where('estado', '!=', 'retirado')
            ->get(['id', 'proyecto_id']);

        $clienteIds = $clientes->pluck('id');

        $facturas = Factura::whereIn('cliente_id', $clienteIds)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get(['id', 'cliente_id', 'total', 'saldo']);

        $pagos = Pago::where('cobrador_id', $cobrador->id)
            ->whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio)
            ->with(['factura:id,cliente_id'])
            ->get(['id', 'factura_id', 'monto']);

        $proyectos = Proyecto::whereIn('id', $clientes->pluck('proyecto_id')->filter()->unique())
            ->get(['id', 'nombre', 'color'])
            ->keyBy('id');

        $proyectosFila = $clientes
            ->groupBy(fn (Cliente $cliente) => $cliente->proyecto_id ?: 0)
            ->map(function ($grupo, $proyectoId) use ($facturas, $pagos, $proyectos) {
                $ids = $grupo->pluck('id');
                $facts = $facturas->whereIn('cliente_id', $ids);
                $recs = $pagos->filter(fn (Pago $pago) => $ids->contains($pago->factura?->cliente_id));
                $proyecto = $proyectos->get((int) $proyectoId);

                return [
                    'id' => $proyectoId ? (int) $proyectoId : null,
                    'nombre' => $proyecto?->nombre ?? 'Sin proyecto',
                    'color' => $proyecto?->color ?? '#64748b',
                    'clientes' => $grupo->count(),
                    'proyectado' => (float) $facts->sum('total'),
                    'pendiente' => (float) $facts->sum('saldo'),
                    'recaudado' => (float) $recs->sum('monto'),
                ];
            })
            ->sortBy('nombre')
            ->values();

        $proyectado = (float) $facturas->sum('total');
        $pendiente = (float) $facturas->sum('saldo');
        $recaudadoCartera = (float) $facturas->sum(fn ($f) => $f->total - $f->saldo);
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
}
