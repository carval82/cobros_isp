<?php

namespace App\Http\Controllers;

use App\Models\PlanServicio;
use Illuminate\Http\Request;

class PlanServicioController extends Controller
{
    public function index()
    {
        $planes = PlanServicio::withCount('servicios')->orderBy('nombre')->get();
        return view('planes.index', compact('planes'));
    }

    public function create()
    {
        return view('planes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'velocidad_bajada' => 'required|integer|min:1',
            'velocidad_subida' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'tipo' => 'required|in:residencial,comercial,empresarial',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->has('activo');

        PlanServicio::create($validated);

        return redirect()->route('planes.index')
            ->with('success', 'Plan creado correctamente');
    }

    public function show(PlanServicio $plane)
    {
        $plane->load('servicios.cliente');
        return view('planes.show', ['plan' => $plane]);
    }

    public function edit(PlanServicio $plane)
    {
        return view('planes.edit', ['plan' => $plane]);
    }

    public function update(Request $request, PlanServicio $plane)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'velocidad_bajada' => 'required|integer|min:1',
            'velocidad_subida' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'tipo' => 'required|in:residencial,comercial,empresarial',
            'activo' => 'boolean',
        ]);

        $validated['activo'] = $request->has('activo');

        $plane->update($validated);

        return redirect()->route('planes.index')
            ->with('success', 'Plan actualizado correctamente');
    }

    public function destroy(PlanServicio $plane)
    {
        if ($plane->servicios()->exists()) {
            return back()->with('error', 'No se puede eliminar el plan porque tiene servicios asociados');
        }

        $plane->delete();

        return redirect()->route('planes.index')
            ->with('success', 'Plan eliminado correctamente');
    }
}
