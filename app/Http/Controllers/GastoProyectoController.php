<?php

namespace App\Http\Controllers;

use App\Models\GastoProyecto;
use App\Models\Proyecto;
use App\Services\LiquidacionProyectoService;
use Illuminate\Http\Request;

class GastoProyectoController extends Controller
{
    public function index(Request $request)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);
        $proyectoId = $request->input('proyecto_id');

        $query = GastoProyecto::with(['proyecto', 'registradoPor'])
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->orderByDesc('fecha');

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $total = (clone $query)->sum('monto');
        $gastos = $query->paginate(30)->withQueryString();

        return view('gastos.index', [
            'gastos' => $gastos,
            'total' => $total,
            'proyectos' => Proyecto::where('activo', true)->orderBy('nombre')->get(),
            'proyectoId' => $proyectoId,
            'mes' => $mes,
            'anio' => $anio,
            'meses' => LiquidacionProyectoService::meses(),
            'categorias' => GastoProyecto::categorias(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'categoria' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'proveedor' => 'nullable|string|max:255',
            'factura_numero' => 'nullable|string|max:50',
            'notas' => 'nullable|string',
        ]);

        $validated['registrado_por'] = $request->user()?->id;
        GastoProyecto::create($validated);

        return back()->with('success', 'Gasto registrado');
    }

    public function update(Request $request, GastoProyecto $gasto)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'proveedor' => 'nullable|string|max:255',
            'factura_numero' => 'nullable|string|max:50',
            'notas' => 'nullable|string',
        ]);

        $gasto->update($validated);

        return back()->with('success', 'Gasto actualizado');
    }

    public function destroy(GastoProyecto $gasto)
    {
        $gasto->delete();

        return back()->with('success', 'Gasto eliminado');
    }
}
