<?php

namespace App\Http\Controllers;

use App\Models\Liquidacion;
use App\Models\Cobrador;
use App\Models\Cobro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiquidacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Liquidacion::with('cobrador');

        if ($request->filled('cobrador_id')) {
            $query->where('cobrador_id', $request->cobrador_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $liquidaciones = $query->orderBy('fecha_liquidacion', 'desc')->paginate(25);
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();

        return view('liquidaciones.index', compact('liquidaciones', 'cobradores'));
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
        return view('liquidaciones.show', ['liquidacion' => $liquidacione]);
    }

    public function edit(Liquidacion $liquidacione)
    {
        return view('liquidaciones.edit', ['liquidacion' => $liquidacione]);
    }

    public function update(Request $request, Liquidacion $liquidacione)
    {
        $validated = $request->validate([
            'observaciones' => 'nullable|string',
        ]);

        $liquidacione->update($validated);

        return redirect()->route('liquidaciones.show', $liquidacione)
            ->with('success', 'Liquidación actualizada correctamente');
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
}
