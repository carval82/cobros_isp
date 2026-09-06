<?php

namespace App\Http\Controllers;

use App\Models\ParticipacionProyecto;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class ParticipacionProyectoController extends Controller
{
    public function store(Request $request, Proyecto $proyecto)
    {
        $validated = $request->validate([
            'socio_nombre' => 'required|string|max:255',
            'socio_documento' => 'nullable|string|max:30',
            'socio_telefono' => 'nullable|string|max:30',
            'porcentaje' => 'required|numeric|min:0.01|max:100',
        ]);

        $total = $proyecto->participaciones()->where('activo', true)->sum('porcentaje') + $validated['porcentaje'];
        if ($total > 100.01) {
            return back()->with('error', 'Los porcentajes no pueden sumar más de 100%. Ahora irían en ' . number_format($total, 2) . '%.');
        }

        $proyecto->participaciones()->create($validated);

        return back()->with('success', 'Socio agregado al proyecto');
    }

    public function update(Request $request, ParticipacionProyecto $participacion)
    {
        $validated = $request->validate([
            'socio_nombre' => 'required|string|max:255',
            'socio_documento' => 'nullable|string|max:30',
            'socio_telefono' => 'nullable|string|max:30',
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'activo' => 'sometimes|boolean',
        ]);

        $total = ParticipacionProyecto::where('proyecto_id', $participacion->proyecto_id)
            ->where('activo', true)
            ->where('id', '!=', $participacion->id)
            ->sum('porcentaje') + $validated['porcentaje'];

        if ($total > 100.01) {
            return back()->with('error', 'Los porcentajes no pueden sumar más de 100%.');
        }

        $validated['activo'] = $request->boolean('activo', $participacion->activo);
        $participacion->update($validated);

        return back()->with('success', 'Participación actualizada');
    }

    public function destroy(ParticipacionProyecto $participacion)
    {
        $participacion->delete();

        return back()->with('success', 'Socio quitado del proyecto');
    }
}
