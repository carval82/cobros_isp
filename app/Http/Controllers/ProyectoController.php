<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::withCount(['clientes', 'planes', 'cobradoresAsignados'])
            ->orderBy('nombre')
            ->get();

        return view('proyectos.index', compact('proyectos'));
    }

    public function create()
    {
        return view('proyectos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:proyectos',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'notas' => 'nullable|string',
        ]);

        Proyecto::create($validated);

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto creado correctamente');
    }

    public function show(Proyecto $proyecto)
    {
        $proyecto->load(['clientes' => function($q) {
            $q->with('servicios.planServicio')->orderBy('nombre');
        }, 'planes', 'cobradoresAsignados']);

        $estadisticas = [
            'total_clientes' => $proyecto->clientes->count(),
            'clientes_activos' => $proyecto->clientes->where('estado', 'activo')->count(),
            'total_planes' => $proyecto->planes->count(),
            'total_cobradores' => $proyecto->cobradoresAsignados->count(),
        ];

        $mes = now()->month;
        $anio = now()->year;

        $clienteIds = $proyecto->clientes->pluck('id');
        
        $facturasDelMes = Factura::whereHas('cliente', function($q) use ($clienteIds) {
            $q->whereIn('id', $clienteIds);
        })->where('mes', $mes)->where('anio', $anio)->get();

        $estadisticas['facturado_mes'] = $facturasDelMes->sum('total');
        $estadisticas['recaudado_mes'] = Pago::whereIn('factura_id', $facturasDelMes->pluck('id'))
            ->whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio)
            ->sum('monto');
        $estadisticas['pendiente_mes'] = $facturasDelMes->sum('saldo');

        return view('proyectos.show', compact('proyecto', 'estadisticas'));
    }

    public function edit(Proyecto $proyecto)
    {
        return view('proyectos.edit', compact('proyecto'));
    }

    public function update(Request $request, Proyecto $proyecto)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:proyectos,codigo,' . $proyecto->id,
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'activo' => 'boolean',
            'notas' => 'nullable|string',
        ]);

        $validated['activo'] = $request->has('activo');
        $proyecto->update($validated);

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto actualizado correctamente');
    }

    public function destroy(Proyecto $proyecto)
    {
        if ($proyecto->clientes()->exists()) {
            return back()->with('error', 'No se puede eliminar el proyecto porque tiene clientes asociados');
        }

        $proyecto->delete();

        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto eliminado correctamente');
    }
}
