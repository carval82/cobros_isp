<?php

namespace App\Http\Controllers;

use App\Models\Cobrador;
use App\Models\User;
use Illuminate\Http\Request;

class CobradorController extends Controller
{
    public function index()
    {
        $cobradores = Cobrador::withCount(['clientes', 'cobros'])
            ->orderBy('nombre')
            ->get();
        return view('cobradores.index', compact('cobradores'));
    }

    public function create()
    {
        return view('cobradores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'documento' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        Cobrador::create($validated);

        return redirect()->route('cobradores.index')
            ->with('success', 'Cobrador creado correctamente');
    }

    public function show(Cobrador $cobradore)
    {
        $cobradore->load(['clientes', 'cobros' => function ($q) {
            $q->orderBy('fecha', 'desc')->limit(20);
        }, 'liquidaciones' => function ($q) {
            $q->orderBy('fecha_liquidacion', 'desc')->limit(10);
        }]);

        return view('cobradores.show', ['cobrador' => $cobradore]);
    }

    public function edit(Cobrador $cobradore)
    {
        return view('cobradores.edit', ['cobrador' => $cobradore]);
    }

    public function update(Request $request, Cobrador $cobradore)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'documento' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $cobradore->update($validated);

        return redirect()->route('cobradores.index')
            ->with('success', 'Cobrador actualizado correctamente');
    }

    public function destroy(Cobrador $cobradore)
    {
        if ($cobradore->cobros()->exists()) {
            return back()->with('error', 'No se puede eliminar el cobrador porque tiene cobros asociados');
        }

        $cobradore->delete();

        return redirect()->route('cobradores.index')
            ->with('success', 'Cobrador eliminado correctamente');
    }
}
