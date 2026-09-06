<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\PlanServicio;
use App\Models\Proyecto;
use App\Services\FacturacionService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::with(['cobrador', 'servicios.planServicio', 'proyecto']);

        if ($request->filled('proyecto_id')) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%")
                  ->orWhere('documento', 'like', "%{$buscar}%")
                  ->orWhere('direccion', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query->orderBy('nombre')->paginate(25);
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();

        return view('clientes.index', compact('clientes', 'proyectos'));
    }

    public function create(Request $request)
    {
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        $planes = PlanServicio::where('activo', true)->orderBy('nombre')->get();
        $proyectoSeleccionado = $request->filled('proyecto_id') ? $request->proyecto_id : null;
        $facturacion = app(FacturacionService::class);
        $periodoNuevo = $facturacion->calcularPrimerPeriodo('nuevo');
        $periodoAntiguo = $facturacion->calcularPrimerPeriodo('antiguo');

        return view('clientes.create', compact(
            'cobradores',
            'proyectos',
            'planes',
            'proyectoSeleccionado',
            'periodoNuevo',
            'periodoAntiguo',
            'facturacion'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyecto_id' => 'nullable|exists:proyectos,id',
            'nombre' => 'required|string|max:150',
            'documento' => 'nullable|string|max:20',
            'tipo_documento' => 'required|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'required|string|max:255',
            'barrio' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'referencia_ubicacion' => 'nullable|string',
            'fecha_instalacion' => 'nullable|date',
            'notas' => 'nullable|string',
            'cobrador_id' => 'nullable|exists:cobradors,id',
            'tipo_alta' => 'required|in:nuevo,antiguo',
            'plan_servicio_id' => 'nullable|exists:plan_servicios,id',
        ]);

        $planId = $validated['plan_servicio_id'] ?? null;
        unset($validated['plan_servicio_id']);

        $cliente = Cliente::create($validated);
        $facturacion = app(FacturacionService::class);
        $facturacion->aplicarPeriodoAlta($cliente, $validated['tipo_alta']);

        $mensaje = 'Cliente creado correctamente.';
        if ($planId) {
            $facturacion->asignarServicioYFacturar($cliente, (int) $planId);
            $mensaje = $cliente->tipo_alta === 'antiguo'
                ? 'Cliente antiguo creado y se generó la factura del mes en curso.'
                : 'Cliente nuevo creado. Primera factura: ' . $cliente->fresh()->etiquetaPrimeraFactura() . '. Este mes queda libre.';
        } elseif ($cliente->tipo_alta === 'nuevo') {
            $mensaje = 'Cliente nuevo creado. Primera factura: ' . $cliente->fresh()->etiquetaPrimeraFactura() . '.';
        }

        return redirect()->route('clientes.show', $cliente)
            ->with('success', $mensaje);
    }

    public function show(Cliente $cliente)
    {
        $cliente->load(['cobrador', 'servicios.planServicio', 'facturas' => function ($q) {
            $q->orderBy('anio', 'desc')->orderBy('mes', 'desc');
        }]);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        $facturacion = app(FacturacionService::class);
        $fechaAlta = $cliente->fecha_instalacion
            ? \Carbon\Carbon::parse($cliente->fecha_instalacion)
            : ($cliente->created_at ?? now());
        $periodoNuevo = $facturacion->calcularPrimerPeriodo('nuevo', $fechaAlta);
        $periodoAntiguo = $facturacion->calcularPrimerPeriodo('antiguo', $fechaAlta);

        return view('clientes.edit', compact(
            'cliente',
            'cobradores',
            'proyectos',
            'facturacion',
            'periodoNuevo',
            'periodoAntiguo'
        ));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'documento' => 'nullable|string|max:20',
            'tipo_documento' => 'required|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'required|string|max:255',
            'barrio' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'referencia_ubicacion' => 'nullable|string',
            'estado' => 'required|in:activo,suspendido,retirado,cortado',
            'fecha_instalacion' => 'nullable|date',
            'notas' => 'nullable|string',
            'cobrador_id' => 'nullable|exists:cobradors,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
            'tipo_alta' => 'required|in:nuevo,antiguo',
        ]);

        $tipoAlta = $validated['tipo_alta'];
        unset($validated['tipo_alta']);
        $cliente->update($validated);

        $mensaje = 'Cliente actualizado correctamente.';
        if ($tipoAlta !== $cliente->tipo_alta) {
            $resultado = app(FacturacionService::class)->corregirTipoAlta($cliente->fresh(), $tipoAlta);
            $mensaje = $resultado['mensaje'];
        }

        return redirect()->route('clientes.show', $cliente)
            ->with('success', $mensaje);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente');
    }

    public function apiIndex(Request $request)
    {
        $clientes = Cliente::with(['servicios.planServicio'])
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        return response()->json($clientes);
    }

    public function apiFacturas(Cliente $cliente)
    {
        $facturas = $cliente->facturas()
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return response()->json($facturas);
    }
}
