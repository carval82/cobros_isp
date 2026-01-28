<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobrador;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Cobro;
use App\Models\Servicio;
use App\Models\PlanServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CobradorAppController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'pin' => 'required|string|min:4',
        ]);

        $cobrador = Cobrador::where('documento', $request->documento)
            ->where('estado', 'activo')
            ->first();

        if (!$cobrador) {
            return response()->json([
                'success' => false,
                'message' => 'Cobrador no encontrado o inactivo'
            ], 401);
        }

        if (!Hash::check($request->pin, $cobrador->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrecto'
            ], 401);
        }

        $token = $cobrador->createToken('cobrador-app')->plainTextToken;

        // Obtener proyectos asignados
        $proyectos = $cobrador->proyectos()->get(['proyectos.id', 'proyectos.nombre', 'proyectos.color']);
        
        // Si no tiene proyectos en la tabla pivot, usar el proyecto_id directo
        if ($proyectos->isEmpty() && $cobrador->proyecto_id) {
            $proyectos = \App\Models\Proyecto::where('id', $cobrador->proyecto_id)
                ->get(['id', 'nombre', 'color']);
        }

        return response()->json([
            'success' => true,
            'cobrador' => [
                'id' => $cobrador->id,
                'nombre' => $cobrador->nombre,
                'documento' => $cobrador->documento,
                'proyecto_id' => $cobrador->proyecto_id,
                'comision_porcentaje' => $cobrador->comision_porcentaje,
                'proyectos' => $proyectos,
            ],
            'token' => $token,
        ]);
    }

    public function proyectos(Request $request)
    {
        $cobrador = $request->user();
        
        // Obtener proyectos de la tabla pivot
        $proyectos = $cobrador->proyectos()
            ->withCount(['clientes' => function($q) {
                $q->where('estado', 'activo');
            }])
            ->get()
            ->map(function($proyecto) use ($cobrador) {
                $facturasPendientes = Factura::whereHas('cliente', function($q) use ($proyecto) {
                    $q->where('proyecto_id', $proyecto->id)
                      ->where('estado', 'activo');
                })->whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count();
                
                return [
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                    'color' => $proyecto->color,
                    'ubicacion' => $proyecto->ubicacion,
                    'comision_porcentaje' => $proyecto->pivot->comision_porcentaje ?? $cobrador->comision_porcentaje,
                    'clientes_asignados' => $proyecto->clientes_count,
                    'facturas_pendientes' => $facturasPendientes,
                ];
            });
        
        // Si no tiene proyectos en pivot, usar proyecto_id directo
        if ($proyectos->isEmpty() && $cobrador->proyecto_id) {
            $proyecto = \App\Models\Proyecto::withCount(['clientes' => function($q) {
                $q->where('estado', 'activo');
            }])->find($cobrador->proyecto_id);
            
            if ($proyecto) {
                $facturasPendientes = Factura::whereHas('cliente', function($q) use ($proyecto) {
                    $q->where('proyecto_id', $proyecto->id)
                      ->where('estado', 'activo');
                })->whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count();
                
                $proyectos = collect([[
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                    'color' => $proyecto->color,
                    'ubicacion' => $proyecto->ubicacion,
                    'comision_porcentaje' => $cobrador->comision_porcentaje,
                    'clientes_asignados' => $proyecto->clientes_count,
                    'facturas_pendientes' => $facturasPendientes,
                ]]);
            }
        }

        return response()->json([
            'success' => true,
            'proyectos' => $proyectos,
        ]);
    }

    public function syncProyecto(Request $request, $proyecto_id)
    {
        $cobrador = $request->user();
        $lastSync = $request->input('last_sync');

        // Verificar que el cobrador tiene acceso al proyecto
        $tieneAcceso = $cobrador->proyectos()->where('proyectos.id', $proyecto_id)->exists() 
            || $cobrador->proyecto_id == $proyecto_id;
            
        if (!$tieneAcceso) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este proyecto'
            ], 403);
        }

        // Generar facturas del mes en curso para clientes con servicio activo
        $this->generarFacturasMesActual($proyecto_id);

        $query = Cliente::with(['proyecto', 'servicios.planServicio'])
            ->where('proyecto_id', $proyecto_id)
            ->where('estado', 'activo');

        if ($lastSync) {
            $query->where('updated_at', '>', $lastSync);
        }

        $clientes = $query->get()->map(function($cliente) {
            $servicio = $cliente->servicios->where('estado', 'activo')->first();
            return [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'celular' => $cliente->celular,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
                'barrio' => $cliente->barrio,
                'referencia_ubicacion' => $cliente->referencia_ubicacion,
                'latitud' => $cliente->latitud,
                'longitud' => $cliente->longitud,
                'proyecto_id' => $cliente->proyecto_id,
                'proyecto_nombre' => $cliente->proyecto?->nombre,
                'servicio' => $servicio ? [
                    'id' => $servicio->id,
                    'plan_nombre' => $servicio->planServicio->nombre ?? 'Sin plan',
                    'precio' => $servicio->precio_mensual,
                    'dia_corte' => $servicio->dia_corte,
                    'dia_pago_limite' => $servicio->dia_pago_limite,
                ] : null,
                'updated_at' => $cliente->updated_at->toISOString(),
            ];
        });

        $facturasPendientes = Factura::with('cliente')
            ->whereHas('cliente', function($q) use ($proyecto_id) {
                $q->where('proyecto_id', $proyecto_id)
                  ->where('estado', 'activo');
            })
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('cliente_id')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get()
            ->map(function($factura) {
                return [
                    'id' => $factura->id,
                    'numero' => $factura->numero,
                    'cliente_id' => $factura->cliente_id,
                    'cliente_nombre' => $factura->cliente->nombre,
                    'mes' => $factura->mes,
                    'anio' => $factura->anio,
                    'periodo' => $factura->periodo,
                    'total' => $factura->total,
                    'saldo' => $factura->saldo,
                    'estado' => $factura->estado,
                    'fecha_vencimiento' => $factura->fecha_vencimiento->format('Y-m-d'),
                    'updated_at' => $factura->updated_at->toISOString(),
                ];
            });

        $planes = PlanServicio::where('activo', true)
            ->where(function($q) use ($proyecto_id) {
                $q->whereNull('proyecto_id')
                  ->orWhere('proyecto_id', $proyecto_id);
            })
            ->get(['id', 'nombre', 'velocidad_bajada', 'velocidad_subida', 'precio']);

        // Estadísticas de cobros del día para este cobrador
        $hoy = Carbon::today();
        $cobrosHoy = Pago::where('cobrador_id', $cobrador->id)
            ->whereDate('fecha_pago', $hoy)
            ->get();
        
        $resumenDia = [
            'cobros_count' => $cobrosHoy->count(),
            'total_cobrado' => $cobrosHoy->sum('monto'),
            'fecha' => $hoy->format('Y-m-d'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'proyecto_id' => $proyecto_id,
                'clientes' => $clientes,
                'facturas_pendientes' => $facturasPendientes,
                'planes' => $planes,
                'resumen_dia' => $resumenDia,
                'server_time' => now()->toISOString(),
            ]
        ]);
    }

    public function sync(Request $request)
    {
        $cobrador = $request->user();
        $lastSync = $request->input('last_sync');

        $query = Cliente::with(['proyecto', 'servicios.planServicio'])
            ->where('cobrador_id', $cobrador->id)
            ->where('estado', 'activo');

        if ($lastSync) {
            $query->where('updated_at', '>', $lastSync);
        }

        $clientes = $query->get()->map(function($cliente) {
            return [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'celular' => $cliente->celular,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
                'barrio' => $cliente->barrio,
                'referencia_ubicacion' => $cliente->referencia_ubicacion,
                'latitud' => $cliente->latitud,
                'longitud' => $cliente->longitud,
                'proyecto_id' => $cliente->proyecto_id,
                'proyecto_nombre' => $cliente->proyecto?->nombre,
                'servicio' => $cliente->servicios->first() ? [
                    'id' => $cliente->servicios->first()->id,
                    'plan_nombre' => $cliente->servicios->first()->planServicio->nombre,
                    'precio' => $cliente->servicios->first()->precio_mensual,
                ] : null,
                'updated_at' => $cliente->updated_at->toISOString(),
            ];
        });

        $facturasPendientes = Factura::with('cliente')
            ->whereHas('cliente', function($q) use ($cobrador) {
                $q->where('cobrador_id', $cobrador->id);
            })
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->get()
            ->map(function($factura) {
                return [
                    'id' => $factura->id,
                    'numero' => $factura->numero,
                    'cliente_id' => $factura->cliente_id,
                    'cliente_nombre' => $factura->cliente->nombre,
                    'mes' => $factura->mes,
                    'anio' => $factura->anio,
                    'periodo' => $factura->periodo,
                    'total' => $factura->total,
                    'saldo' => $factura->saldo,
                    'estado' => $factura->estado,
                    'fecha_vencimiento' => $factura->fecha_vencimiento->format('Y-m-d'),
                    'updated_at' => $factura->updated_at->toISOString(),
                ];
            });

        $cobroAbierto = Cobro::where('cobrador_id', $cobrador->id)
            ->where('estado', 'abierto')
            ->first();

        $planes = PlanServicio::where('activo', true)
            ->where(function($q) use ($cobrador) {
                $q->whereNull('proyecto_id')
                  ->orWhere('proyecto_id', $cobrador->proyecto_id);
            })
            ->get(['id', 'nombre', 'velocidad_bajada', 'velocidad_subida', 'precio']);

        return response()->json([
            'success' => true,
            'data' => [
                'clientes' => $clientes,
                'facturas_pendientes' => $facturasPendientes,
                'cobro_abierto' => $cobroAbierto ? [
                    'id' => $cobroAbierto->id,
                    'fecha' => $cobroAbierto->fecha->format('Y-m-d'),
                ] : null,
                'planes' => $planes,
                'server_time' => now()->toISOString(),
            ]
        ]);
    }

    public function registrarPago(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto' => 'required|numeric|min:1',
            'metodo_pago' => 'required|in:efectivo,transferencia,nequi,daviplata',
            'fecha_pago' => 'required|date',
            'observaciones' => 'nullable|string',
            'offline_id' => 'nullable|string',
        ]);

        $cobrador = $request->user();
        $factura = Factura::with('cliente')->findOrFail($request->factura_id);

        // Verificar que el cobrador tiene acceso al proyecto del cliente
        $proyecto_id = $factura->cliente->proyecto_id;
        $tieneAcceso = $cobrador->proyectos()->where('proyectos.id', $proyecto_id)->exists() 
            || $cobrador->proyecto_id == $proyecto_id;
            
        if (!$tieneAcceso) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para cobrar esta factura'
            ], 403);
        }

        if ($request->offline_id) {
            $existente = Pago::where('referencia_pago', 'OFFLINE-' . $request->offline_id)->first();
            if ($existente) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pago ya sincronizado',
                    'pago' => $existente,
                    'duplicado' => true,
                ]);
            }
        }

        $cobroAbierto = Cobro::firstOrCreate(
            ['cobrador_id' => $cobrador->id, 'estado' => 'abierto'],
            ['fecha' => now(), 'total_recaudado' => 0]
        );

        DB::beginTransaction();
        try {
            $pago = Pago::create([
                'factura_id' => $factura->id,
                'cobro_id' => $cobroAbierto->id,
                'cobrador_id' => $cobrador->id,
                'monto' => $request->monto,
                'fecha_pago' => $request->fecha_pago,
                'metodo_pago' => $request->metodo_pago,
                'notas' => $request->observaciones,
                'referencia_pago' => $request->offline_id ? 'OFFLINE-' . $request->offline_id : null,
            ]);

            // Recargar la factura para obtener el saldo actualizado (el modelo Pago ya actualiza el saldo en el evento created)
            $factura->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago' => [
                    'id' => $pago->id,
                    'monto' => $pago->monto,
                    'factura_id' => $pago->factura_id,
                    'factura_saldo' => $factura->saldo,
                    'factura_estado' => $factura->estado,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registrarCliente(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'documento' => 'nullable|string|max:20',
            'tipo_documento' => 'required|string|max:10',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'barrio' => 'nullable|string|max:100',
            'referencia_ubicacion' => 'nullable|string',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'proyecto_id' => 'nullable|exists:proyectos,id',
            'plan_servicio_id' => 'nullable|exists:plan_servicios,id',
            'offline_id' => 'nullable|string',
        ]);

        $cobrador = $request->user();

        if ($request->offline_id) {
            $existente = Cliente::where('notas', 'like', '%OFFLINE-' . $request->offline_id . '%')->first();
            if ($existente) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente ya sincronizado',
                    'cliente' => $existente,
                    'duplicado' => true,
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $cliente = Cliente::create([
                'proyecto_id' => $request->proyecto_id ?? $cobrador->proyecto_id,
                'nombre' => $request->nombre,
                'documento' => $request->documento,
                'tipo_documento' => $request->tipo_documento,
                'celular' => $request->celular,
                'direccion' => $request->direccion,
                'barrio' => $request->barrio,
                'referencia_ubicacion' => $request->referencia_ubicacion,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
                'cobrador_id' => $cobrador->id,
                'fecha_instalacion' => now(),
                'notas' => $request->offline_id ? 'OFFLINE-' . $request->offline_id : null,
            ]);

            if ($request->plan_servicio_id) {
                $plan = PlanServicio::find($request->plan_servicio_id);
                Servicio::create([
                    'cliente_id' => $cliente->id,
                    'plan_servicio_id' => $plan->id,
                    'fecha_inicio' => now(),
                    'dia_corte' => 1,
                    'dia_pago_limite' => 10,
                    'estado' => 'activo',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cliente registrado correctamente',
                'cliente' => [
                    'id' => $cliente->id,
                    'codigo' => $cliente->codigo,
                    'nombre' => $cliente->nombre,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cerrarCobro(Request $request)
    {
        $cobrador = $request->user();

        $cobro = Cobro::where('cobrador_id', $cobrador->id)
            ->where('estado', 'abierto')
            ->first();

        if (!$cobro) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cobro abierto'
            ], 404);
        }

        $cobro->estado = 'cerrado';
        $cobro->save();

        return response()->json([
            'success' => true,
            'message' => 'Cobro cerrado correctamente',
            'cobro' => [
                'id' => $cobro->id,
                'total_recaudado' => $cobro->total_recaudado,
            ],
        ]);
    }

    public function resumenDia(Request $request)
    {
        $cobrador = $request->user();
        $fecha = $request->input('fecha', now()->format('Y-m-d'));

        $pagosHoy = Pago::where('cobrador_id', $cobrador->id)
            ->whereDate('fecha_pago', $fecha)
            ->get();

        $totalEfectivo = $pagosHoy->where('metodo_pago', 'efectivo')->sum('monto');
        $totalTransferencia = $pagosHoy->where('metodo_pago', 'transferencia')->sum('monto');
        $totalNequi = $pagosHoy->where('metodo_pago', 'nequi')->sum('monto');
        $totalDaviplata = $pagosHoy->where('metodo_pago', 'daviplata')->sum('monto');

        return response()->json([
            'success' => true,
            'resumen' => [
                'fecha' => $fecha,
                'total_pagos' => $pagosHoy->count(),
                'total_recaudado' => $pagosHoy->sum('monto'),
                'por_metodo' => [
                    'efectivo' => $totalEfectivo,
                    'transferencia' => $totalTransferencia,
                    'nequi' => $totalNequi,
                    'daviplata' => $totalDaviplata,
                ],
            ],
        ]);
    }

    private function generarFacturasMesActual($proyecto_id)
    {
        $mes = now()->month;
        $anio = now()->year;
        $diaActual = now()->day;

        // Obtener clientes activos del proyecto con servicio activo
        $clientes = Cliente::with(['servicios' => function($q) {
                $q->where('estado', 'activo');
            }])
            ->where('proyecto_id', $proyecto_id)
            ->where('estado', 'activo')
            ->get();

        foreach ($clientes as $cliente) {
            $servicio = $cliente->servicios->first();
            
            if (!$servicio) continue;

            // Verificar si ya existe factura para este mes
            $facturaExiste = Factura::where('cliente_id', $cliente->id)
                ->where('servicio_id', $servicio->id)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->exists();

            if ($facturaExiste) continue;

            // Generar factura si el día actual es >= día de corte
            if ($diaActual >= $servicio->dia_corte) {
                $precio = $servicio->precio_mensual;
                $diaVencimiento = $servicio->dia_pago_limite ?? 10;
                
                // Calcular fecha de vencimiento
                $fechaVencimiento = Carbon::create($anio, $mes, min($diaVencimiento, 28));
                if ($diaVencimiento > 28) {
                    $fechaVencimiento = Carbon::create($anio, $mes, 1)->endOfMonth();
                }

                Factura::create([
                    'cliente_id' => $cliente->id,
                    'servicio_id' => $servicio->id,
                    'mes' => $mes,
                    'anio' => $anio,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => $fechaVencimiento,
                    'subtotal' => $precio,
                    'descuento' => 0,
                    'recargo' => 0,
                    'total' => $precio,
                    'saldo' => $precio,
                    'estado' => 'pendiente',
                    'concepto' => 'Servicio de Internet - ' . ($servicio->planServicio->nombre ?? 'Plan'),
                ]);
            }
        }
    }
}
