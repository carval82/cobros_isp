<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Servicio;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with(['cliente', 'servicio.planServicio']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('mes') && $request->filled('anio')) {
            $query->where('mes', $request->mes)->where('anio', $request->anio);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        $facturas = $query->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('facturas.index', compact('facturas'));
    }

    public function create()
    {
        $servicios = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->get();
        return view('facturas.create', compact('servicios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'servicio_id' => 'required|exists:servicios,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
            'subtotal' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'concepto' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $servicio = Servicio::with('cliente')->find($validated['servicio_id']);
        
        $descuento = $validated['descuento'] ?? 0;
        $total = $validated['subtotal'] - $descuento;

        $factura = Factura::create([
            'cliente_id' => $servicio->cliente_id,
            'servicio_id' => $validated['servicio_id'],
            'mes' => $validated['mes'],
            'anio' => $validated['anio'],
            'fecha_emision' => now(),
            'fecha_vencimiento' => Carbon::create($validated['anio'], $validated['mes'], $servicio->dia_pago_limite),
            'subtotal' => $validated['subtotal'],
            'descuento' => $descuento,
            'total' => $total,
            'saldo' => $total,
            'concepto' => $validated['concepto'],
            'notas' => $validated['notas'],
        ]);

        return redirect()->route('facturas.show', $factura)
            ->with('success', 'Factura creada correctamente');
    }

    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'servicio.planServicio', 'pagos.cobrador']);
        return view('facturas.show', compact('factura'));
    }

    public function edit(Factura $factura)
    {
        return view('facturas.edit', compact('factura'));
    }

    public function update(Request $request, Factura $factura)
    {
        $validated = $request->validate([
            'descuento' => 'nullable|numeric|min:0',
            'recargo' => 'nullable|numeric|min:0',
            'concepto' => 'nullable|string',
            'notas' => 'nullable|string',
            'estado' => 'required|in:pendiente,pagada,parcial,vencida,anulada',
        ]);

        $total = $factura->subtotal - ($validated['descuento'] ?? 0) + ($validated['recargo'] ?? 0);
        $validated['total'] = $total;

        if ($validated['estado'] == 'anulada') {
            $validated['saldo'] = 0;
        }

        $factura->update($validated);

        return redirect()->route('facturas.show', $factura)
            ->with('success', 'Factura actualizada correctamente');
    }

    public function destroy(Factura $factura)
    {
        if ($factura->pagos()->exists()) {
            return back()->with('error', 'No se puede eliminar la factura porque tiene pagos asociados');
        }

        $factura->delete();

        return redirect()->route('facturas.index')
            ->with('success', 'Factura eliminada correctamente');
    }

    public function generarMes(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
        ]);

        $servicios = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->get();

        $generadas = 0;
        $omitidas = 0;

        $facturacion = app(\App\Services\FacturacionService::class);
        foreach ($servicios as $servicio) {
            $factura = $facturacion->generarFacturaPeriodo($servicio, (int) $validated['mes'], (int) $validated['anio']);
            if ($factura) {
                $generadas++;
            } else {
                $omitidas++;
            }
        }

        return redirect()->route('facturas.index', ['mes' => $validated['mes'], 'anio' => $validated['anio']])
            ->with('success', "Se generaron {$generadas} facturas. {$omitidas} omitidas (ya existían o mes libre).");
    }

    public function pdf(Factura $factura)
    {
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);
        
        $pdf = Pdf::loadView('facturas.pdf.factura', compact('factura'));
        
        return $pdf->stream("factura-{$factura->numero}.pdf");
    }

    public function descargarPdf(Factura $factura)
    {
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);
        
        $pdf = Pdf::loadView('facturas.pdf.factura', compact('factura'));
        
        return $pdf->download("factura-{$factura->numero}.pdf");
    }

    public function generarMesProyecto(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
            'proyecto_id' => 'nullable|exists:proyectos,id',
        ]);

        $query = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo');

        if ($request->filled('proyecto_id')) {
            $query->whereHas('cliente', function($q) use ($validated) {
                $q->where('proyecto_id', $validated['proyecto_id']);
            });
        }

        $servicios = $query->get();

        $generadas = 0;
        $omitidas = 0;

        $facturacion = app(\App\Services\FacturacionService::class);
        foreach ($servicios as $servicio) {
            $factura = $facturacion->generarFacturaPeriodo($servicio, (int) $validated['mes'], (int) $validated['anio']);
            if ($factura) {
                $generadas++;
            } else {
                $omitidas++;
            }
        }

        $proyectoNombre = $request->filled('proyecto_id') 
            ? Proyecto::find($validated['proyecto_id'])->nombre 
            : 'Todos los proyectos';

        return redirect()->route('facturas.index', ['mes' => $validated['mes'], 'anio' => $validated['anio']])
            ->with('success', "{$proyectoNombre}: Se generaron {$generadas} facturas. {$omitidas} omitidas.");
    }

    public function resetSeptiembre()
    {
        $mes = 9;
        $anio = (int) now()->year;

        $eliminadas = Factura::query()->count();
        Factura::query()->delete();

        $resultado = $this->generarFacturasActivas($mes, $anio);

        return redirect()->route('facturas.index', ['mes' => $mes, 'anio' => $anio])
            ->with('success', "Estados de cuenta limpios: se ocultaron {$eliminadas} facturas anteriores y se cargaron {$resultado['generadas']} de septiembre {$anio}.");
    }

    private function generarFacturasActivas(int $mes, int $anio, ?int $proyectoId = null): array
    {
        $query = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo');

        if ($proyectoId) {
            $query->whereHas('cliente', function ($q) use ($proyectoId) {
                $q->where('proyecto_id', $proyectoId);
            });
        }

        $generadas = 0;
        $omitidas = 0;

        foreach ($query->get() as $servicio) {
            if ($servicio->cliente && ! $servicio->cliente->puedeFacturarseEn($mes, $anio)) {
                $omitidas++;
                continue;
            }

            if ($servicio->tieneFacturaMes($mes, $anio)) {
                $omitidas++;
                continue;
            }

            $precio = $servicio->precio_mensual;
            $diaLimite = min((int) ($servicio->dia_pago_limite ?: 10), Carbon::create($anio, $mes, 1)->daysInMonth);

            Factura::create([
                'cliente_id' => $servicio->cliente_id,
                'servicio_id' => $servicio->id,
                'mes' => $mes,
                'anio' => $anio,
                'fecha_emision' => Carbon::create($anio, $mes, 1),
                'fecha_vencimiento' => Carbon::create($anio, $mes, $diaLimite),
                'subtotal' => $precio,
                'total' => $precio,
                'saldo' => $precio,
                'concepto' => 'Servicio de Internet - ' . ($servicio->planServicio->nombre ?? 'Plan'),
            ]);

            $generadas++;
        }

        return compact('generadas', 'omitidas');
    }
}
