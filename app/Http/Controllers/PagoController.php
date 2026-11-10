<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Factura;
use App\Models\Cobrador;
use App\Models\Cobro;
use App\Models\Liquidacion;
use App\Models\Proyecto;
use App\Services\LiquidacionProyectoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pago::with(['factura.cliente', 'cobrador']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_pago', $request->fecha);
        }

        if ($request->filled('cobrador_id')) {
            $query->where('cobrador_id', $request->cobrador_id);
        }

        $pagos = $query->orderBy('created_at', 'desc')->paginate(25);
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();

        return view('pagos.index', compact('pagos', 'cobradores'));
    }

    public function create(Request $request)
    {
        $facturas = Factura::with('cliente')
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('cliente_id')
            ->get();
        
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get();
        $cobrosAbiertos = Cobro::where('estado', 'abierto')->with('cobrador')->get();
        
        $facturaSeleccionada = $request->filled('factura_id') 
            ? Factura::with('cliente')->find($request->factura_id) 
            : null;

        return view('pagos.create', compact('facturas', 'cobradores', 'cobrosAbiertos', 'facturaSeleccionada'));
    }

    public function masivo(Request $request)
    {
        $proyectoId = (int) $request->input('proyecto_id');
        $mes = (int) $request->input('mes');
        $anio = (int) $request->input('anio');
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }
        if ($anio < 2020) {
            $anio = now()->year;
        }

        $facturas = collect();
        if ($proyectoId) {
            $facturas = Factura::with('cliente')
                ->where('saldo', '>', 0)
                ->whereNotIn('estado', ['anulada', 'pagada'])
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->whereHas('cliente', fn ($q) => $q->where('proyecto_id', $proyectoId))
                ->get()
                ->sortBy(fn (Factura $factura) => mb_strtolower($factura->cliente->nombre ?? ''))
                ->values();
        }

        return view('pagos.masivo', [
            'proyectos' => Proyecto::where('activo', true)->orderBy('nombre')->get(),
            'cobradores' => Cobrador::where('estado', 'activo')->orderBy('nombre')->get(),
            'meses' => LiquidacionProyectoService::meses(),
            'facturas' => $facturas,
            'proyectoId' => $proyectoId,
            'mes' => $mes,
            'anio' => $anio,
        ]);
    }

    public function aplicarMasivo(Request $request)
    {
        $request->merge([
            'cobrador_id' => $request->input('cobrador_id') ?: null,
        ]);

        $validated = $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:2035',
            'facturas' => 'required|array|min:1',
            'facturas.*' => 'integer|exists:facturas,id',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,nequi,daviplata,tarjeta,otro',
            'cobrador_id' => 'nullable|exists:cobradors,id',
            'referencia_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        $facturas = Factura::with('cliente')
            ->whereIn('id', $validated['facturas'])
            ->where('saldo', '>', 0)
            ->whereNotIn('estado', ['anulada', 'pagada'])
            ->where('mes', $validated['mes'])
            ->where('anio', $validated['anio'])
            ->whereHas('cliente', fn ($q) => $q->where('proyecto_id', $validated['proyecto_id']))
            ->get();

        if ($facturas->isEmpty()) {
            return back()->with('error', 'Ninguna de las facturas marcadas tiene saldo por cobrar.');
        }

        $cantidad = 0;
        $total = 0.0;

        DB::transaction(function () use ($facturas, $validated, &$cantidad, &$total) {
            $cobroId = null;
            if (! empty($validated['cobrador_id'])) {
                $cobro = Cobro::firstOrCreate(
                    ['cobrador_id' => $validated['cobrador_id'], 'estado' => 'abierto'],
                    ['fecha' => $validated['fecha_pago'], 'total_recaudado' => 0]
                );
                $cobroId = $cobro->id;
            }

            foreach ($facturas as $factura) {
                $factura->refresh();
                $saldo = round((float) $factura->saldo, 2);
                if ($saldo <= 0) {
                    continue;
                }

                Pago::create([
                    'factura_id' => $factura->id,
                    'monto' => $saldo,
                    'fecha_pago' => $validated['fecha_pago'],
                    'metodo_pago' => $validated['metodo_pago'],
                    'cobrador_id' => $validated['cobrador_id'] ?? null,
                    'cobro_id' => $cobroId,
                    'referencia_pago' => $validated['referencia_pago'] ?? null,
                    'notas' => $validated['notas'] ?? 'Pago masivo',
                    'user_id' => auth()->id(),
                ]);

                $cantidad++;
                $total += $saldo;
            }
        });

        return redirect()
            ->route('pagos.masivo', [
                'proyecto_id' => $validated['proyecto_id'],
                'mes' => $validated['mes'],
                'anio' => $validated['anio'],
            ])
            ->with('success', 'Se aplicaron '.$cantidad.' pagos por $'.number_format($total, 0, ',', '.').'. Cada factura marcada quedó saldada.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto' => 'required|numeric|min:1',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,nequi,daviplata,tarjeta,otro',
            'cobrador_id' => 'nullable|exists:cobradors,id',
            'cobro_id' => 'nullable|exists:cobros,id',
            'referencia_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
        ]);

        $factura = Factura::find($validated['factura_id']);
        
        if ($validated['monto'] > $factura->saldo) {
            return back()->with('error', 'El monto del pago no puede ser mayor al saldo de la factura');
        }

        $pago = Pago::create($validated);

        return redirect()->route('pagos.show', $pago)
            ->with('success', 'Pago registrado correctamente');
    }

    public function show(Pago $pago)
    {
        $pago->load(['factura.cliente', 'cobrador', 'cobro']);
        return view('pagos.show', compact('pago'));
    }

    public function edit(Pago $pago)
    {
        return view('pagos.edit', compact('pago'));
    }

    public function update(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'notas' => 'nullable|string',
        ]);

        $pago->update($validated);

        return redirect()->route('pagos.show', $pago)
            ->with('success', 'Pago actualizado correctamente');
    }

    public function destroy(Pago $pago)
    {
        $factura = $pago->factura;
        $monto = $pago->monto;
        
        $pago->delete();
        
        $factura->saldo += $monto;
        if ($factura->saldo >= $factura->total) {
            $factura->estado = 'pendiente';
        } else {
            $factura->estado = 'parcial';
        }
        $factura->save();

        return redirect()->route('pagos.index')
            ->with('success', 'Pago eliminado y saldo restaurado');
    }

    public function limpiarHistorial()
    {
        $pagos = Pago::withTrashed()->count();
        $cobros = Cobro::count();
        $liquidaciones = Liquidacion::count();

        Pago::withTrashed()->forceDelete();
        Cobro::query()->delete();
        Liquidacion::query()->delete();

        return redirect()->route('pagos.index')
            ->with('success', "Mes en cero: se borraron {$pagos} pagos, {$cobros} cobros y {$liquidaciones} liquidaciones. Las facturas de septiembre siguen.");
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto' => 'required|numeric|min:1',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,nequi,daviplata,tarjeta,otro',
            'cobrador_id' => 'nullable|exists:cobradors,id',
            'cobro_id' => 'nullable|exists:cobros,id',
        ]);

        $factura = Factura::find($validated['factura_id']);
        
        if ($validated['monto'] > $factura->saldo) {
            return response()->json(['error' => 'Monto mayor al saldo'], 422);
        }

        $pago = Pago::create($validated);

        return response()->json([
            'success' => true,
            'pago' => $pago->load('factura.cliente'),
        ]);
    }
}
