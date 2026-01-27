<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                ];
            });

        return response()->json([
            'success' => true,
            'cobradores' => $cobradores,
        ]);
    }
}
