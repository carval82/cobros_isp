<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Factura;
use App\Models\Cobrador;
use App\Models\Cobro;
use Illuminate\Http\Request;

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
