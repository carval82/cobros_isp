<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Cobrador;
use Illuminate\Http\Request;

class CobroController extends Controller
{
    public function index(Request $request)
    {
        $query = Cobro::with('cobrador');

        if ($request->filled('cobrador_id')) {
            $query->where('cobrador_id', $request->cobrador_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $cobros = $query->orderBy('fecha', 'desc')->paginate(25);
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();

        return view('cobros.index', compact('cobros', 'cobradores'));
    }

    public function create()
    {
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();
        return view('cobros.create', compact('cobradores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cobrador_id' => 'required|exists:cobradors,id',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        $cobro = Cobro::create($validated);

        return redirect()->route('cobros.show', $cobro)
            ->with('success', 'Cobro iniciado correctamente');
    }

    public function show(Cobro $cobro)
    {
        $cobro->load(['cobrador', 'pagos.factura.cliente']);
        return view('cobros.show', compact('cobro'));
    }

    public function edit(Cobro $cobro)
    {
        return view('cobros.edit', compact('cobro'));
    }

    public function update(Request $request, Cobro $cobro)
    {
        $validated = $request->validate([
            'observaciones' => 'nullable|string',
        ]);

        $cobro->update($validated);

        return redirect()->route('cobros.show', $cobro)
            ->with('success', 'Cobro actualizado correctamente');
    }

    public function destroy(Cobro $cobro)
    {
        if ($cobro->pagos()->exists()) {
            return back()->with('error', 'No se puede eliminar el cobro porque tiene pagos asociados');
        }

        $cobro->delete();

        return redirect()->route('cobros.index')
            ->with('success', 'Cobro eliminado correctamente');
    }

    public function cerrar(Cobro $cobro)
    {
        if ($cobro->estado !== 'abierto') {
            return back()->with('error', 'El cobro ya está cerrado');
        }

        $cobro->cerrar();

        return redirect()->route('cobros.show', $cobro)
            ->with('success', 'Cobro cerrado correctamente. Total recaudado: $' . number_format($cobro->total_recaudado, 0, ',', '.'));
    }

    public function apiShow(Cobro $cobro)
    {
        $cobro->load(['cobrador', 'pagos.factura.cliente']);
        return response()->json($cobro);
    }
}
