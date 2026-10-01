<?php

namespace App\Http\Controllers;

use App\Models\Liquidacion;
use App\Models\Cobrador;
use App\Models\Cobro;
use App\Models\Pago;
use App\Services\AtribucionPago;
use App\Services\CobradorInformeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiquidacionController extends Controller
{
    public function index(Request $request)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);
        $verTodas = $request->boolean('todas');

        $query = Liquidacion::query()
            ->select('liquidacions.*')
            ->join('cobradors', 'cobradors.id', '=', 'liquidacions.cobrador_id')
            ->with('cobrador');

        if (! $verTodas) {
            $query->whereMonth('liquidacions.fecha_desde', $mes)
                ->whereYear('liquidacions.fecha_desde', $anio);
        }

        if ($request->filled('cobrador_id')) {
            $query->where('liquidacions.cobrador_id', $request->cobrador_id);
        }

        if ($request->filled('estado')) {
            $query->where('liquidacions.estado', $request->estado);
        }

        $liquidaciones = $query
            ->orderByDesc('liquidacions.fecha_desde')
            ->orderBy('cobradors.nombre')
            ->paginate(25)
            ->withQueryString();
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();
        $meses = CobradorInformeService::meses();

        return view('liquidaciones.index', compact('liquidaciones', 'cobradores', 'meses', 'mes', 'anio', 'verTodas'));
    }

    public function create()
    {
        $cobradores = Cobrador::where('estado', 'activo')
            ->whereHas('cobros', function ($q) {
                $q->where('estado', 'cerrado');
            })
            ->orderBy('nombre')
            ->get();

        return view('liquidaciones.create', compact('cobradores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cobrador_id' => 'required|exists:cobradors,id',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'observaciones' => 'nullable|string',
        ]);

        $cobrador = Cobrador::find($validated['cobrador_id']);

        $cobros = Cobro::where('cobrador_id', $validated['cobrador_id'])
            ->where('estado', 'cerrado')
            ->whereBetween('fecha', [$validated['fecha_desde'], $validated['fecha_hasta']])
            ->whereNull('liquidacion_id')
            ->get();

        if ($cobros->isEmpty()) {
            return back()->with('error', 'No hay cobros cerrados sin liquidar en el período seleccionado');
        }

        DB::transaction(function () use ($validated, $cobros, $cobrador) {
            $totalRecaudado = $cobros->sum('total_recaudado');
            $totalComision = $cobros->sum('total_comision');
            $cantidadPagos = $cobros->sum('cantidad_pagos');

            $liquidacion = Liquidacion::create([
                'cobrador_id' => $validated['cobrador_id'],
                'fecha_desde' => $validated['fecha_desde'],
                'fecha_hasta' => $validated['fecha_hasta'],
                'fecha_liquidacion' => now(),
                'total_recaudado' => $totalRecaudado,
                'total_comision' => $totalComision,
                'total_a_entregar' => $totalRecaudado - $totalComision,
                'cantidad_cobros' => $cobros->count(),
                'cantidad_pagos' => $cantidadPagos,
                'observaciones' => $validated['observaciones'],
                'user_id' => auth()->id(),
            ]);

            foreach ($cobros as $cobro) {
                $cobro->update([
                    'liquidacion_id' => $liquidacion->id,
                    'estado' => 'liquidado',
                ]);
            }
        });

        return redirect()->route('liquidaciones.index')
            ->with('success', 'Liquidación creada correctamente');
    }

    public function show(Liquidacion $liquidacione)
    {
        $liquidacione->load(['cobrador', 'cobros.pagos']);
        $pagos = AtribucionPago::aplicar(
            Pago::with(['factura.cliente'])->where('cobrador_id', $liquidacione->cobrador_id),
            (int) $liquidacione->fecha_desde->month,
            (int) $liquidacione->fecha_desde->year
        )->orderBy('fecha_pago')->orderBy('id')->get();

        return view('liquidaciones.show', [
            'liquidacion' => $liquidacione,
            'pagos' => $pagos,
        ]);
    }

    public function edit(Liquidacion $liquidacione)
    {
        return view('liquidaciones.edit', ['liquidacion' => $liquidacione]);
    }

    public function update(Request $request, Liquidacion $liquidacione)
    {
        $validated = $request->validate([
            'total_recaudado' => 'required|numeric|min:0',
            'total_comision' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $liquidacione->update([
            'total_recaudado' => $validated['total_recaudado'],
            'total_comision' => $validated['total_comision'],
            'total_a_entregar' => $validated['total_recaudado'] - $validated['total_comision'],
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return redirect()->route('liquidaciones.show', $liquidacione)
            ->with('success', 'Liquidación actualizada correctamente');
    }

    public function recalcular(Liquidacion $liquidacion, CobradorInformeService $service)
    {
        $service->recalcular($liquidacion);

        return redirect()->route('liquidaciones.show', $liquidacion)
            ->with('success', 'Liquidación recalculada con los pagos del período');
    }

    public function destroy(Liquidacion $liquidacione)
    {
        if ($liquidacione->estado === 'pagada') {
            return back()->with('error', 'No se puede eliminar una liquidación pagada');
        }

        DB::transaction(function () use ($liquidacione) {
            $liquidacione->cobros()->update([
                'liquidacion_id' => null,
                'estado' => 'cerrado',
            ]);

            $liquidacione->delete();
        });

        return redirect()->route('liquidaciones.index')
            ->with('success', 'Liquidación eliminada correctamente');
    }

    public function pagar(Liquidacion $liquidacion)
    {
        if ($liquidacion->estado !== 'pendiente') {
            return back()->with('error', 'La liquidación ya fue pagada o anulada');
        }

        $liquidacion->update(['estado' => 'pagada']);

        return redirect()->route('liquidaciones.show', $liquidacion)
            ->with('success', 'Liquidación marcada como pagada');
    }

    public function informeMensual(Request $request, CobradorInformeService $service)
    {
        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $informe = $service->informeMensual($mes, $anio);
        $meses = CobradorInformeService::meses();

        return view('liquidaciones.informe-mensual', compact('informe', 'meses', 'mes', 'anio'));
    }

    public function informeCobrador(Request $request, Cobrador $cobrador, CobradorInformeService $service)
    {
        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $detalle = $service->detalleCobrador($cobrador, $mes, $anio);

        return view('liquidaciones.informe-cobrador', compact('detalle', 'cobrador'));
    }

    public function generarMensual(Request $request, CobradorInformeService $service)
    {
        $validated = $request->validate([
            'cobrador_id' => 'required|exists:cobradors,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
        ]);

        $cobrador = Cobrador::findOrFail($validated['cobrador_id']);
        $liquidacion = $service->generarLiquidacion(
            $cobrador,
            (int) $validated['mes'],
            (int) $validated['anio'],
            auth()->id()
        );

        return redirect()->route('liquidaciones.show', $liquidacion)
            ->with('success', 'Informe mensual y liquidación listos para ' . $cobrador->nombre);
    }

    public function generarMensualTodos(Request $request, CobradorInformeService $service)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
        ]);

        $cobradores = Cobrador::where('estado', 'activo')->get();
        $generadas = 0;

        foreach ($cobradores as $cobrador) {
            $antes = Liquidacion::where('cobrador_id', $cobrador->id)
                ->whereMonth('fecha_desde', $validated['mes'])
                ->whereYear('fecha_desde', $validated['anio'])
                ->where('estado', '!=', 'anulada')
                ->exists();

            $service->generarLiquidacion(
                $cobrador,
                (int) $validated['mes'],
                (int) $validated['anio'],
                auth()->id()
            );

            if (! $antes) {
                $generadas++;
            }
        }

        return redirect()->route('liquidaciones.informe', [
            'mes' => $validated['mes'],
            'anio' => $validated['anio'],
        ])->with('success', "Se generaron {$generadas} liquidaciones del mes. Si ya existían, se respetaron.");
    }
}
