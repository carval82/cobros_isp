<?php

namespace App\Http\Controllers;

use App\Models\Cobrador;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CobradorController extends Controller
{
    public function index(Request $request)
    {
        $query = Cobrador::with('proyectos')->withCount(['clientes', 'cobros']);

        if ($request->filled('proyecto_id')) {
            $query->whereHas('proyectos', function($q) use ($request) {
                $q->where('proyectos.id', $request->proyecto_id);
            });
        }

        $cobradores = $query->orderBy('nombre')->get();
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        
        return view('cobradores.index', compact('cobradores', 'proyectos'));
    }

    public function create()
    {
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        return view('cobradores.create', compact('proyectos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'exists:proyectos,id',
            'nombre' => 'required|string|max:150',
            'documento' => 'required|string|max:20|unique:cobradors,documento',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'pin' => 'required|string|min:4|max:6',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        $validated['pin'] = Hash::make($validated['pin']);
        
        // Extraer proyectos antes de crear
        $proyectos = $validated['proyectos'] ?? [];
        unset($validated['proyectos']);

        $cobrador = Cobrador::create($validated);
        
        // Sincronizar proyectos asignados
        $cobrador->proyectos()->sync($proyectos);

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
        $cobradore->load('proyectos');
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        return view('cobradores.edit', ['cobrador' => $cobradore, 'proyectos' => $proyectos]);
    }

    public function update(Request $request, Cobrador $cobradore)
    {
        $validated = $request->validate([
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'exists:proyectos,id',
            'nombre' => 'required|string|max:150',
            'documento' => 'required|string|max:20|unique:cobradors,documento,' . $cobradore->id,
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'pin' => 'nullable|string|min:4|max:6',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
            'estado' => 'required|in:activo,inactivo',
        ]);

        if (!empty($validated['pin'])) {
            $validated['pin'] = Hash::make($validated['pin']);
        } else {
            unset($validated['pin']);
        }

        // Extraer proyectos antes de actualizar
        $proyectos = $validated['proyectos'] ?? [];
        unset($validated['proyectos']);

        $cobradore->update($validated);
        
        // Sincronizar proyectos asignados
        $cobradore->proyectos()->sync($proyectos);

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
