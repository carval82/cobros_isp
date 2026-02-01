<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Ticket;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteAppController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'pin' => 'required|string|min:4|max:4',
        ]);

        $documento = $request->documento;
        $cliente = Cliente::where('estado', 'activo')
            ->where(function($q) use ($documento) {
                $q->where('documento', $documento)
                  ->orWhere('documento', 'CC ' . $documento)
                  ->orWhere('documento', 'LIKE', '%' . $documento);
            })
            ->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado o inactivo'
            ], 401);
        }

        // Verificar PIN (últimos 4 dígitos del documento o PIN personalizado)
        $pinEsperado = substr($cliente->documento, -4);
        $pinValido = false;
        
        if ($cliente->pin) {
            $pinValido = Hash::check($request->pin, $cliente->pin);
        } else {
            $pinValido = $request->pin === $pinEsperado;
        }

        if (!$pinValido) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrecto'
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
                'celular' => $cliente->celular,
                'email' => $cliente->email,
                'direccion' => $cliente->direccion,
                'proyecto_nombre' => $cliente->proyecto?->nombre,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
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

    public function estadoCuenta(Request $request)
    {
        $cliente = $request->user();
        $cliente->load(['proyecto', 'servicios.planServicio']);

        $facturasPendientes = $cliente->facturas()
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->count();

        $saldoPendiente = $cliente->facturas()
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->sum('saldo');

        $servicio = $cliente->servicioActivo();
        $ultimaFactura = $cliente->facturas()->orderBy('anio', 'desc')->orderBy('mes', 'desc')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'saldo_pendiente' => $saldoPendiente,
                'facturas_pendientes' => $facturasPendientes,
                'servicio' => $servicio ? [
                    'plan_nombre' => $servicio->planServicio->nombre ?? 'Sin plan',
                    'velocidad_bajada' => $servicio->planServicio->velocidad_bajada ?? 0,
                    'velocidad_subida' => $servicio->planServicio->velocidad_subida ?? 0,
                    'precio' => $servicio->precio_mensual,
                    'estado' => $servicio->estado,
                ] : null,
                'ultima_factura' => $ultimaFactura ? [
                    'periodo' => $ultimaFactura->periodo,
                    'total' => $ultimaFactura->total,
                    'estado' => $ultimaFactura->estado,
                    'fecha_vencimiento' => $ultimaFactura->fecha_vencimiento->format('d/m/Y'),
                ] : null,
            ],
        ]);
    }

    public function getFacturas(Request $request)
    {
        $cliente = $request->user();

        $facturas = $cliente->facturas()
            ->with('pagos')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->limit(24)
            ->get()
            ->map(function($f) {
                return [
                    'id' => $f->id,
                    'numero' => $f->numero,
                    'periodo' => $f->periodo,
                    'total' => $f->total,
                    'saldo' => $f->saldo,
                    'estado' => $f->estado,
                    'fecha_vencimiento' => $f->fecha_vencimiento->format('d/m/Y'),
                    'pagos' => $f->pagos->map(function($p) {
                        return [
                            'monto' => $p->monto,
                            'fecha_pago' => $p->fecha_pago->format('d/m/Y'),
                            'metodo_pago' => $p->metodo_pago,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $facturas,
        ]);
    }

    public function getTickets(Request $request)
    {
        $cliente = $request->user();

        $tickets = $cliente->tickets()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'tipo' => $t->tipo,
                    'asunto' => $t->asunto,
                    'descripcion' => $t->descripcion,
                    'estado' => $t->estado,
                    'respuesta' => $t->respuesta,
                    'fecha_respuesta' => $t->fecha_respuesta?->format('d/m/Y H:i'),
                    'created_at' => $t->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function crearTicket(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:daño,cobro,soporte,otro',
            'asunto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
        ]);

        $cliente = $request->user();

        $ticket = Ticket::create([
            'cliente_id' => $cliente->id,
            'proyecto_id' => $cliente->proyecto_id,
            'tipo' => $request->tipo,
            'asunto' => $request->asunto,
            'descripcion' => $request->descripcion,
            'estado' => 'abierto',
            'prioridad' => $request->tipo === 'daño' ? 'alta' : 'media',
        ]);

        // Enviar notificación push a admins
        try {
            $pushService = new PushNotificationService();
            $pushService->sendToAdmins(
                '🎫 Nuevo Ticket: ' . $request->tipo,
                $cliente->nombre . ': ' . $request->asunto,
                [
                    'screen' => 'AdminTickets',
                    'ticket_id' => $ticket->id,
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error enviando push notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Reporte creado correctamente',
            'ticket' => [
                'id' => $ticket->id,
                'tipo' => $ticket->tipo,
                'asunto' => $ticket->asunto,
                'estado' => $ticket->estado,
            ],
        ]);
    }

    public function getPerfil(Request $request)
    {
        $cliente = $request->user();
        $cliente->load(['proyecto', 'servicios.planServicio']);

        $servicio = $cliente->servicioActivo();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'celular' => $cliente->celular,
                'email' => $cliente->email,
                'direccion' => $cliente->direccion,
                'proyecto_nombre' => $cliente->proyecto?->nombre,
                'servicio' => $servicio ? [
                    'plan_nombre' => $servicio->planServicio->nombre ?? 'Sin plan',
                    'precio' => $servicio->precio_mensual,
                ] : null,
            ],
        ]);
    }

    public function actualizarPerfil(Request $request)
    {
        $request->validate([
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
        ]);

        $cliente = $request->user();
        $cliente->update($request->only(['celular', 'email', 'direccion']));

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente',
        ]);
    }

    public function cambiarPin(Request $request)
    {
        $request->validate([
            'pin_actual' => 'required|string|min:4|max:4',
            'pin_nuevo' => 'required|string|min:4|max:4',
        ]);

        $cliente = $request->user();

        // Verificar PIN actual
        $pinEsperado = substr($cliente->documento, -4);
        $pinValido = false;

        if ($cliente->pin) {
            $pinValido = Hash::check($request->pin_actual, $cliente->pin);
        } else {
            $pinValido = $request->pin_actual === $pinEsperado;
        }

        if (!$pinValido) {
            return response()->json([
                'success' => false,
                'message' => 'PIN actual incorrecto'
            ], 400);
        }

        $cliente->update([
            'pin' => Hash::make($request->pin_nuevo)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PIN actualizado correctamente',
        ]);
    }
}
