<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Cliente;
use App\Models\PlanServicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::with(['cliente', 'planServicio']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_servicio_id', $request->plan_id);
        }

        $servicios = $query->orderBy('created_at', 'desc')->paginate(25);
        $planes = PlanServicio::where('activo', true)->orderBy('nombre')->get();

        return view('servicios.index', compact('servicios', 'planes'));
    }

    public function create(Request $request)
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $planes = PlanServicio::where('activo', true)->orderBy('nombre')->get();
        $clienteSeleccionado = $request->filled('cliente_id') ? Cliente::find($request->cliente_id) : null;

        return view('servicios.create', compact('clientes', 'planes', 'clienteSeleccionado'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'plan_servicio_id' => 'required|exists:plan_servicios,id',
            'ip_asignada' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:20',
            'equipo_modelo' => 'nullable|string|max:100',
            'equipo_serial' => 'nullable|string|max:100',
            'dia_corte' => 'required|integer|min:1|max:28',
            'dia_pago_limite' => 'required|integer|min:1|max:28',
            'fecha_inicio' => 'required|date',
            'precio_especial' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);

        Servicio::create($validated);

        return redirect()->route('clientes.show', $validated['cliente_id'])
            ->with('success', 'Servicio creado correctamente');
    }

    public function show(Servicio $servicio)
    {
        $servicio->load(['cliente', 'planServicio', 'facturas']);
        return view('servicios.show', compact('servicio'));
    }

    public function edit(Servicio $servicio)
    {
        $planes = PlanServicio::orderBy('nombre')->get();
        return view('servicios.edit', compact('servicio', 'planes'));
    }

    public function update(Request $request, Servicio $servicio)
    {
        $validated = $request->validate([
            'plan_servicio_id' => 'required|exists:plan_servicios,id',
            'ip_asignada' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:20',
            'equipo_modelo' => 'nullable|string|max:100',
            'equipo_serial' => 'nullable|string|max:100',
            'dia_corte' => 'required|integer|min:1|max:28',
            'dia_pago_limite' => 'required|integer|min:1|max:28',
            'precio_especial' => 'nullable|numeric|min:0',
            'estado' => 'required|in:activo,suspendido,cancelado,cortado',
            'notas' => 'nullable|string',
        ]);

        $servicio->update($validated);

        return redirect()->route('clientes.show', $servicio->cliente_id)
            ->with('success', 'Servicio actualizado correctamente');
    }

    public function destroy(Servicio $servicio)
    {
        if ($servicio->facturas()->exists()) {
            return back()->with('error', 'No se puede eliminar el servicio porque tiene facturas asociadas');
        }

        $clienteId = $servicio->cliente_id;
        $servicio->delete();

        return redirect()->route('clientes.show', $clienteId)
            ->with('success', 'Servicio eliminado correctamente');
    }
}
