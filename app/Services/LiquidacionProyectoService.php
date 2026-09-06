<?php

namespace App\Services;

use App\Models\GastoProyecto;
use App\Models\Pago;
use App\Models\ParticipacionProyecto;
use App\Models\Proyecto;
use Carbon\Carbon;

class LiquidacionProyectoService
{
    public static function meses(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    public function calcular(Proyecto $proyecto, int $mes, int $anio): array
    {
        $proyecto->loadMissing('cobradoresAsignados');

        $pagos = Pago::with('cobrador')
            ->whereHas('factura.cliente', function ($q) use ($proyecto) {
                $q->where('proyecto_id', $proyecto->id);
            })
            ->whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio)
            ->get();

        $totalIngresos = (float) $pagos->sum('monto');

        $gastos = GastoProyecto::where('proyecto_id', $proyecto->id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        $totalGastos = (float) $gastos->sum('monto');

        $gastosPorCategoria = $gastos->groupBy('categoria')->map(function ($items, $categoria) {
            return [
                'categoria' => $categoria,
                'nombre' => GastoProyecto::categorias()[$categoria] ?? $categoria,
                'total' => (float) $items->sum('monto'),
                'items' => $items,
            ];
        })->values();

        $comisionesDetalle = $pagos->groupBy('cobrador_id')->map(function ($items) use ($proyecto) {
            $cobrador = $items->first()->cobrador;
            $recaudado = (float) $items->sum('monto');
            $porcentaje = $this->comisionCobrador($proyecto, $cobrador);
            $comision = round($recaudado * ($porcentaje / 100), 2);

            return [
                'cobrador' => $cobrador?->nombre ?? 'Sin cobrador',
                'recaudado' => $recaudado,
                'porcentaje_comision' => $porcentaje,
                'comision' => $comision,
            ];
        })->values();

        $totalComisiones = (float) $comisionesDetalle->sum('comision');
        $utilidad = round($totalIngresos - $totalGastos, 2);
        $utilidadNeta = round($utilidad - $totalComisiones, 2);

        $participaciones = ParticipacionProyecto::where('proyecto_id', $proyecto->id)
            ->where('activo', true)
            ->orderByDesc('porcentaje')
            ->get();

        $socios = $participaciones->map(function (ParticipacionProyecto $p) use ($utilidad, $utilidadNeta, $totalGastos) {
            $factor = $p->porcentaje / 100;

            return [
                'id' => $p->id,
                'socio' => $p->socio_nombre,
                'documento' => $p->socio_documento,
                'telefono' => $p->socio_telefono,
                'porcentaje' => (float) $p->porcentaje,
                'gastos_proporcional' => round($totalGastos * $factor, 2),
                'liquidacion' => round($utilidad * $factor, 2),
                'liquidacion_neta' => round($utilidadNeta * $factor, 2),
            ];
        });

        $periodo = Carbon::create($anio, $mes, 1);

        return [
            'proyecto' => $proyecto,
            'periodo' => [
                'mes' => $mes,
                'anio' => $anio,
                'nombre' => (self::meses()[$mes] ?? $periodo->monthName) . ' ' . $anio,
            ],
            'ingresos' => $totalIngresos,
            'gastos' => $totalGastos,
            'comisiones' => $totalComisiones,
            'utilidad' => $utilidad,
            'utilidad_neta' => $utilidadNeta,
            'cantidad_pagos' => $pagos->count(),
            'gastos_detalle' => $gastos,
            'gastos_por_categoria' => $gastosPorCategoria,
            'comisiones_detalle' => $comisionesDetalle,
            'socios' => $socios,
            'total_porcentaje' => (float) $participaciones->sum('porcentaje'),
        ];
    }

    public function resumenTodos(int $mes, int $anio)
    {
        return Proyecto::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Proyecto $proyecto) => $this->calcular($proyecto, $mes, $anio));
    }

    private function comisionCobrador(Proyecto $proyecto, $cobrador): float
    {
        if (! $cobrador) {
            return 0;
        }

        $asignado = $proyecto->cobradoresAsignados->firstWhere('id', $cobrador->id);
        if ($asignado && $asignado->pivot->comision_porcentaje !== null) {
            return (float) $asignado->pivot->comision_porcentaje;
        }

        return (float) $cobrador->comision_porcentaje;
    }
}
