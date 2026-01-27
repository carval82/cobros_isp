<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteAppController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'pin' => 'required|string|min:4',
        ]);

        $cliente = Cliente::where('documento', $request->documento)
            ->where('estado', 'activo')
            ->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado o inactivo'
            ], 401);
        }

        if (!$cliente->pin || !Hash::check($request->pin, $cliente->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrecto o no configurado'
            ], 401);
        }

        $token = $cliente->createToken('cliente-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'cliente' => [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'direccion' => $cliente->direccion,
            ],
            'token' => $token,
        ]);
    }

    public function cuenta(Request $request)
    {
        $cliente = $request->user();
        
        $cliente->load(['proyecto', 'servicios.planServicio']);

        $facturasPendientes = Factura::whereHas('servicio', function($q) use ($cliente) {
            $q->where('cliente_id', $cliente->id);
        })->whereIn('estado', ['pendiente', 'parcial', 'vencida'])->sum('saldo');

        return response()->json([
            'success' => true,
            'cuenta' => [
                'cliente' => [
                    'id' => $cliente->id,
                    'codigo' => $cliente->codigo,
                    'nombre' => $cliente->nombre,
                    'documento' => $cliente->documento,
                    'celular' => $cliente->celular,
                    'direccion' => $cliente->direccion,
                    'barrio' => $cliente->barrio,
                    'proyecto' => $cliente->proyecto?->nombre,
                ],
                'servicio' => $cliente->servicios->first() ? [
                    'plan' => $cliente->servicios->first()->planServicio->nombre ?? 'Sin plan',
                    'precio' => $cliente->servicios->first()->precio_mensual,
                    'estado' => $cliente->servicios->first()->estado,
                ] : null,
                'saldo_pendiente' => $facturasPendientes,
            ],
        ]);
    }

    public function facturas(Request $request)
    {
        $cliente = $request->user();

        $facturas = Factura::whereHas('servicio', function($q) use ($cliente) {
            $q->where('cliente_id', $cliente->id);
        })
        ->orderBy('anio', 'desc')
        ->orderBy('mes', 'desc')
        ->limit(12)
        ->get()
        ->map(function($f) {
            return [
                'id' => $f->id,
                'numero' => $f->numero,
                'periodo' => $f->periodo,
                'total' => $f->total,
                'saldo' => $f->saldo,
                'estado' => $f->estado,
                'fecha_vencimiento' => $f->fecha_vencimiento->format('Y-m-d'),
            ];
        });

        return response()->json([
            'success' => true,
            'facturas' => $facturas,
        ]);
    }

    public function pagos(Request $request)
    {
        $cliente = $request->user();

        $pagos = Pago::whereHas('factura.servicio', function($q) use ($cliente) {
            $q->where('cliente_id', $cliente->id);
        })
        ->with('factura')
        ->orderBy('fecha_pago', 'desc')
        ->limit(20)
        ->get()
        ->map(function($p) {
            return [
                'id' => $p->id,
                'monto' => $p->monto,
                'fecha_pago' => $p->fecha_pago->format('Y-m-d'),
                'metodo_pago' => $p->metodo_pago,
                'factura_periodo' => $p->factura->periodo,
            ];
        });

        return response()->json([
            'success' => true,
            'pagos' => $pagos,
        ]);
    }
}
