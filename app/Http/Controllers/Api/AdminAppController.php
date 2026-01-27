<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\PlanServicio;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAppController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $token = $user->createToken('admin-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    public function dashboard(Request $request)
    {
        $mes = now()->month;
        $anio = now()->year;

        $totalClientes = Cliente::where('estado', 'activo')->count();
        $totalCobradores = Cobrador::where('estado', 'activo')->count();
        $totalProyectos = Proyecto::where('activo', true)->count();

        $facturasDelMes = Factura::where('mes', $mes)->where('anio', $anio)->get();
        $facturadoMes = $facturasDelMes->sum('total');
        $recaudadoMes = Pago::whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio)
            ->sum('monto');
        $pendienteMes = $facturasDelMes->sum('saldo');

        $pagosHoy = Pago::whereDate('fecha_pago', today())->get();

        return response()->json([
            'success' => true,
            'dashboard' => [
                'total_clientes' => $totalClientes,
                'total_cobradores' => $totalCobradores,
                'total_proyectos' => $totalProyectos,
                'facturado_mes' => $facturadoMes,
                'recaudado_mes' => $recaudadoMes,
                'pendiente_mes' => $pendienteMes,
                'pagos_hoy' => [
                    'cantidad' => $pagosHoy->count(),
                    'total' => $pagosHoy->sum('monto'),
                ],
            ],
        ]);
    }

    public function proyectos(Request $request)
    {
        $proyectos = Proyecto::withCount(['clientes', 'cobradoresAsignados'])
            ->orderBy('nombre')
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'color' => $p->color,
                    'activo' => $p->activo,
                    'clientes_count' => $p->clientes_count,
                    'cobradores_count' => $p->cobradores_asignados_count,
                ];
            });

        return response()->json([
            'success' => true,
            'proyectos' => $proyectos,
        ]);
    }

    public function clientes(Request $request)
    {
        $query = Cliente::with(['proyecto', 'cobrador']);
        
        if ($request->proyecto_id) {
            $query->where('proyecto_id', $request->proyecto_id);
        }
        
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $clientes = $query->orderBy('nombre')
            ->limit(50)
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'codigo' => $c->codigo,
                    'nombre' => $c->nombre,
                    'documento' => $c->documento,
                    'celular' => $c->celular,
                    'direccion' => $c->direccion,
                    'estado' => $c->estado,
                    'proyecto' => $c->proyecto?->nombre,
                    'cobrador' => $c->cobrador?->nombre,
                ];
            });

        return response()->json([
            'success' => true,
            'clientes' => $clientes,
        ]);
    }

    public function cobradores(Request $request)
    {
        $cobradores = Cobrador::with('proyectos')
            ->withCount('clientes')
            ->orderBy('nombre')
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'documento' => $c->documento,
                    'celular' => $c->celular,
                    'estado' => $c->estado,
                    'comision_porcentaje' => $c->comision_porcentaje,
                    'clientes_count' => $c->clientes_count,
                    'proyectos' => $c->proyectos->pluck('nombre'),
                    'proyectos_ids' => $c->proyectos->pluck('id'),
                ];
            });

        return response()->json([
            'success' => true,
            'cobradores' => $cobradores,
        ]);
    }

    // ==================== CRUD CLIENTES ====================
    
    public function storeCliente(Request $request)
    {
        $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'nombre' => 'required|string|max:255',
            'documento' => 'required|string|max:20',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'barrio' => 'nullable|string|max:100',
            'cobrador_id' => 'nullable|exists:cobradors,id',
        ]);

        $cliente = Cliente::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'cliente' => $cliente,
        ]);
    }

    public function updateCliente(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'documento' => 'sometimes|string|max:20',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'estado' => 'sometimes|in:activo,suspendido,retirado',
            'cobrador_id' => 'nullable|exists:cobradors,id',
        ]);

        $cliente->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'cliente' => $cliente,
        ]);
    }

    public function deleteCliente($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente',
        ]);
    }

    public function getCliente($id)
    {
        $cliente = Cliente::with(['proyecto', 'cobrador', 'servicios.planServicio'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'cliente' => [
                'id' => $cliente->id,
                'codigo' => $cliente->codigo,
                'nombre' => $cliente->nombre,
                'documento' => $cliente->documento,
                'celular' => $cliente->celular,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'direccion' => $cliente->direccion,
                'barrio' => $cliente->barrio,
                'estado' => $cliente->estado,
                'proyecto_id' => $cliente->proyecto_id,
                'proyecto' => $cliente->proyecto?->nombre,
                'cobrador_id' => $cliente->cobrador_id,
                'cobrador' => $cliente->cobrador?->nombre,
                'servicios' => $cliente->servicios->map(fn($s) => [
                    'id' => $s->id,
                    'plan' => $s->planServicio?->nombre,
                    'precio' => $s->precio_mensual,
                    'estado' => $s->estado,
                ]),
            ],
        ]);
    }

    // ==================== CRUD COBRADORES ====================
    
    public function storeCobrador(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'documento' => 'required|string|max:20|unique:cobradors,documento',
            'pin' => 'required|string|min:4',
            'celular' => 'nullable|string|max:20',
            'comision_porcentaje' => 'nullable|numeric|min:0|max:100',
            'proyectos' => 'nullable|array',
        ]);

        $cobrador = Cobrador::create([
            'nombre' => $request->nombre,
            'documento' => $request->documento,
            'pin' => Hash::make($request->pin),
            'celular' => $request->celular,
            'comision_porcentaje' => $request->comision_porcentaje ?? 0,
            'estado' => 'activo',
        ]);

        if ($request->proyectos) {
            $cobrador->proyectos()->sync($request->proyectos);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobrador creado exitosamente',
            'cobrador' => $cobrador,
        ]);
    }

    public function updateCobrador(Request $request, $id)
    {
        $cobrador = Cobrador::findOrFail($id);
        
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'documento' => 'sometimes|string|max:20',
            'pin' => 'nullable|string|min:4',
            'celular' => 'nullable|string|max:20',
            'comision_porcentaje' => 'nullable|numeric|min:0|max:100',
            'estado' => 'sometimes|in:activo,inactivo',
            'proyectos' => 'nullable|array',
        ]);

        $data = $request->except(['pin', 'proyectos']);
        if ($request->pin) {
            $data['pin'] = Hash::make($request->pin);
        }
        
        $cobrador->update($data);

        if ($request->has('proyectos')) {
            $cobrador->proyectos()->sync($request->proyectos);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobrador actualizado exitosamente',
            'cobrador' => $cobrador,
        ]);
    }

    public function deleteCobrador($id)
    {
        $cobrador = Cobrador::findOrFail($id);
        $cobrador->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cobrador eliminado exitosamente',
        ]);
    }

    // ==================== CRUD PLANES DE SERVICIO ====================
    
    public function planes(Request $request)
    {
        $query = PlanServicio::with('proyecto');
        
        if ($request->proyecto_id) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        $planes = $query->orderBy('nombre')->get()->map(fn($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'precio' => $p->precio,
            'velocidad_bajada' => $p->velocidad_bajada,
            'velocidad_subida' => $p->velocidad_subida,
            'activo' => $p->activo,
            'proyecto_id' => $p->proyecto_id,
            'proyecto' => $p->proyecto?->nombre,
        ]);

        return response()->json([
            'success' => true,
            'planes' => $planes,
        ]);
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'proyecto_id' => 'required|exists:proyectos,id',
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'velocidad_bajada' => 'nullable|integer',
            'velocidad_subida' => 'nullable|integer',
            'descripcion' => 'nullable|string',
        ]);

        $plan = PlanServicio::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan creado exitosamente',
            'plan' => $plan,
        ]);
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = PlanServicio::findOrFail($id);
        
        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'precio' => 'sometimes|numeric|min:0',
            'velocidad_bajada' => 'nullable|integer',
            'velocidad_subida' => 'nullable|integer',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        $plan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plan actualizado exitosamente',
            'plan' => $plan,
        ]);
    }

    public function deletePlan($id)
    {
        $plan = PlanServicio::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan eliminado exitosamente',
        ]);
    }

    // ==================== CRUD PROYECTOS ====================
    
    public function storeProyecto(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20|unique:proyectos,codigo',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $proyecto = Proyecto::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proyecto creado exitosamente',
            'proyecto' => $proyecto,
        ]);
    }

    public function updateProyecto(Request $request, $id)
    {
        $proyecto = Proyecto::findOrFail($id);
        
        $request->validate([
            'codigo' => 'sometimes|string|max:20',
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'activo' => 'sometimes|boolean',
        ]);

        $proyecto->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proyecto actualizado exitosamente',
            'proyecto' => $proyecto,
        ]);
    }

    public function deleteProyecto($id)
    {
        $proyecto = Proyecto::findOrFail($id);
        $proyecto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proyecto eliminado exitosamente',
        ]);
    }

    // ==================== PAGOS ====================
    
    public function pagos(Request $request)
    {
        $query = Pago::with(['factura.servicio.cliente', 'cobrador']);
        
        if ($request->fecha_desde) {
            $query->whereDate('fecha_pago', '>=', $request->fecha_desde);
        }
        if ($request->fecha_hasta) {
            $query->whereDate('fecha_pago', '<=', $request->fecha_hasta);
        }
        if ($request->cobrador_id) {
            $query->where('cobrador_id', $request->cobrador_id);
        }

        $pagos = $query->orderBy('fecha_pago', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'monto' => $p->monto,
                'fecha_pago' => $p->fecha_pago->format('Y-m-d H:i'),
                'metodo_pago' => $p->metodo_pago,
                'referencia' => $p->referencia,
                'cliente' => $p->factura?->servicio?->cliente?->nombre,
                'cobrador' => $p->cobrador?->nombre,
                'factura_periodo' => $p->factura?->periodo,
            ]);

        return response()->json([
            'success' => true,
            'pagos' => $pagos,
        ]);
    }

    public function anularPago($id)
    {
        $pago = Pago::findOrFail($id);
        
        DB::transaction(function() use ($pago) {
            $factura = $pago->factura;
            if ($factura) {
                $factura->saldo += $pago->monto;
                $factura->estado = $factura->saldo >= $factura->total ? 'pendiente' : 'parcial';
                $factura->save();
            }
            $pago->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pago anulado exitosamente',
        ]);
    }

    // ==================== CRUD SERVICIOS ====================
    
    public function serviciosCliente($clienteId)
    {
        $cliente = Cliente::with(['servicios.planServicio'])->findOrFail($clienteId);
        
        $servicios = $cliente->servicios->map(fn($s) => [
            'id' => $s->id,
            'plan_id' => $s->plan_servicio_id,
            'plan_nombre' => $s->planServicio?->nombre,
            'precio_plan' => $s->planServicio?->precio,
            'precio_especial' => $s->precio_especial,
            'precio_mensual' => $s->precio_mensual,
            'ip_asignada' => $s->ip_asignada,
            'mac_address' => $s->mac_address,
            'dia_corte' => $s->dia_corte,
            'dia_pago_limite' => $s->dia_pago_limite,
            'fecha_inicio' => $s->fecha_inicio?->format('Y-m-d'),
            'estado' => $s->estado,
        ]);

        return response()->json([
            'success' => true,
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ],
            'servicios' => $servicios,
        ]);
    }

    public function storeServicio(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'plan_servicio_id' => 'required|exists:plan_servicios,id',
            'ip_asignada' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:17',
            'dia_corte' => 'nullable|integer|min:1|max:28',
            'dia_pago_limite' => 'nullable|integer|min:1|max:28',
            'precio_especial' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
        ]);

        $servicio = Servicio::create([
            'cliente_id' => $request->cliente_id,
            'plan_servicio_id' => $request->plan_servicio_id,
            'ip_asignada' => $request->ip_asignada,
            'mac_address' => $request->mac_address,
            'dia_corte' => $request->dia_corte ?? 1,
            'dia_pago_limite' => $request->dia_pago_limite ?? 10,
            'precio_especial' => $request->precio_especial,
            'fecha_inicio' => $request->fecha_inicio ?? now(),
            'estado' => 'activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio asignado exitosamente',
            'servicio' => $servicio,
        ]);
    }

    public function updateServicio(Request $request, $id)
    {
        $servicio = Servicio::findOrFail($id);
        
        $request->validate([
            'plan_servicio_id' => 'sometimes|exists:plan_servicios,id',
            'ip_asignada' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:17',
            'dia_corte' => 'nullable|integer|min:1|max:28',
            'dia_pago_limite' => 'nullable|integer|min:1|max:28',
            'precio_especial' => 'nullable|numeric|min:0',
            'estado' => 'sometimes|in:activo,suspendido,cancelado',
        ]);

        $servicio->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado exitosamente',
            'servicio' => $servicio,
        ]);
    }

    public function deleteServicio($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado exitosamente',
        ]);
    }

    // ==================== DATOS AUXILIARES ====================
    
    public function datosFormularios()
    {
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']);
        $cobradores = Cobrador::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'documento']);
        $planes = PlanServicio::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'precio', 'proyecto_id']);

        return response()->json([
            'success' => true,
            'proyectos' => $proyectos,
            'cobradores' => $cobradores,
            'planes' => $planes,
        ]);
    }
}
