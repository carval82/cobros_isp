<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParticipacionProyecto;
use App\Models\Proyecto;
use App\Models\Pago;
use App\Models\GastoProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SocioAppController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'pin' => 'required|string|min:4|max:4',
        ]);

        $documento = $request->documento;
        
        // Buscar socio por documento en participaciones
        $participacion = ParticipacionProyecto::where('activo', true)
            ->where(function($q) use ($documento) {
                $q->where('socio_documento', $documento)
                  ->orWhere('socio_documento', 'CC ' . $documento)
                  ->orWhere('socio_documento', 'LIKE', '%' . $documento);
            })
            ->first();

        if (!$participacion) {
            return response()->json([
                'success' => false,
                'message' => 'Socio no encontrado o inactivo'
            ], 401);
        }

        // PIN por defecto: últimos 4 dígitos del documento
        $docLimpio = preg_replace('/[^0-9]/', '', $participacion->socio_documento);
        $pinEsperado = substr($docLimpio, -4);

        if ($request->pin !== $pinEsperado) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrecto'
            ], 401);
        }

        // Crear token temporal usando el documento como identificador
        $token = base64_encode($participacion->socio_documento . ':' . time() . ':' . md5($participacion->socio_documento . env('APP_KEY')));

        return response()->json([
            'success' => true,
            'socio' => [
                'nombre' => $participacion->socio_nombre,
                'documento' => $participacion->socio_documento,
                'telefono' => $participacion->socio_telefono,
            ],
            'token' => $token,
        ]);
    }

    public function proyectos(Request $request)
    {
        $documento = $this->getDocumentoFromToken($request);
        
        if (!$documento) {
            return response()->json(['success' => false, 'message' => 'Token inválido'], 401);
        }

        $participaciones = ParticipacionProyecto::where('activo', true)
            ->where(function($q) use ($documento) {
                $q->where('socio_documento', $documento)
                  ->orWhere('socio_documento', 'LIKE', '%' . preg_replace('/[^0-9]/', '', $documento));
            })
            ->with('proyecto')
            ->get();

        $proyectos = $participaciones->map(function($p) {
            return [
                'id' => $p->proyecto_id,
                'nombre' => $p->proyecto->nombre ?? 'Sin nombre',
                'porcentaje' => $p->porcentaje,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $proyectos,
        ]);
    }

    public function liquidacion(Request $request, $proyecto_id)
    {
        $documento = $this->getDocumentoFromToken($request);
        
        if (!$documento) {
            return response()->json(['success' => false, 'message' => 'Token inválido'], 401);
        }

        // Verificar que el socio tiene participación en este proyecto
        $participacion = ParticipacionProyecto::where('proyecto_id', $proyecto_id)
            ->where('activo', true)
            ->where(function($q) use ($documento) {
                $q->where('socio_documento', $documento)
                  ->orWhere('socio_documento', 'LIKE', '%' . preg_replace('/[^0-9]/', '', $documento));
            })
            ->first();

        if (!$participacion) {
            return response()->json(['success' => false, 'message' => 'No tiene acceso a este proyecto'], 403);
        }

        $proyecto = Proyecto::find($proyecto_id);
        $mes = $request->get('mes', Carbon::now()->month);
        $anio = $request->get('anio', Carbon::now()->year);

        // Calcular ingresos del mes
        $ingresos = Pago::whereHas('factura.servicio.cliente', function($q) use ($proyecto_id) {
            $q->where('proyecto_id', $proyecto_id);
        })
        ->whereMonth('fecha_pago', $mes)
        ->whereYear('fecha_pago', $anio)
        ->sum('monto');

        // Calcular gastos del mes
        $gastos = GastoProyecto::where('proyecto_id', $proyecto_id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->sum('monto');

        // Calcular utilidad y participación
        $utilidad = $ingresos - $gastos;
        $miParticipacion = $utilidad * ($participacion->porcentaje / 100);

        // Obtener historial de los últimos 6 meses
        $historial = [];
        for ($i = 0; $i < 6; $i++) {
            $fecha = Carbon::now()->subMonths($i);
            $m = $fecha->month;
            $a = $fecha->year;

            $ing = Pago::whereHas('factura.servicio.cliente', function($q) use ($proyecto_id) {
                $q->where('proyecto_id', $proyecto_id);
            })
            ->whereMonth('fecha_pago', $m)
            ->whereYear('fecha_pago', $a)
            ->sum('monto');

            $gas = GastoProyecto::where('proyecto_id', $proyecto_id)
                ->whereMonth('fecha', $m)
                ->whereYear('fecha', $a)
                ->sum('monto');

            $util = $ing - $gas;

            $historial[] = [
                'mes' => $fecha->format('M Y'),
                'mes_num' => $m,
                'anio' => $a,
                'ingresos' => $ing,
                'gastos' => $gas,
                'utilidad' => $util,
                'mi_participacion' => $util * ($participacion->porcentaje / 100),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'proyecto' => [
                    'id' => $proyecto->id,
                    'nombre' => $proyecto->nombre,
                ],
                'socio' => [
                    'nombre' => $participacion->socio_nombre,
                    'porcentaje' => $participacion->porcentaje,
                ],
                'periodo' => [
                    'mes' => $mes,
                    'anio' => $anio,
                ],
                'resumen' => [
                    'ingresos' => $ingresos,
                    'gastos' => $gastos,
                    'utilidad' => $utilidad,
                    'mi_participacion' => $miParticipacion,
                ],
                'historial' => $historial,
            ],
        ]);
    }

    private function getDocumentoFromToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) return null;

        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            if (count($parts) >= 3) {
                return $parts[0];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
