<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParticipacionProyecto;
use App\Models\Proyecto;
use App\Models\GastoProyecto;
use App\Services\LiquidacionProyectoService;
use Illuminate\Http\Request;
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

        $proyecto = Proyecto::findOrFail($proyecto_id);
        $mes = (int) $request->get('mes', Carbon::now()->month);
        $anio = (int) $request->get('anio', Carbon::now()->year);
        $service = app(LiquidacionProyectoService::class);
        $informe = $service->calcular($proyecto, $mes, $anio);
        $mio = $informe['socios']->firstWhere('id', $participacion->id);

        $gastosDetalle = $informe['gastos_detalle']->map(function ($g) {
            return [
                'id' => $g->id,
                'fecha' => $g->fecha?->format('d/m/Y'),
                'categoria' => $g->categoria,
                'categoria_nombre' => GastoProyecto::categorias()[$g->categoria] ?? $g->categoria,
                'descripcion' => $g->descripcion,
                'proveedor' => $g->proveedor,
                'monto' => (float) $g->monto,
            ];
        })->values();

        $socios = $informe['socios']->map(function ($s) use ($participacion) {
            return [
                'nombre' => $s['socio'],
                'porcentaje' => $s['porcentaje'],
                'gastos' => $s['gastos_proporcional'],
                'liquidacion' => $s['liquidacion'],
                'es_mio' => (int) $s['id'] === (int) $participacion->id,
            ];
        })->values();

        $historial = [];
        for ($i = 0; $i < 6; $i++) {
            $fecha = Carbon::now()->subMonths($i);
            $item = $service->calcular($proyecto, $fecha->month, $fecha->year);
            $mioMes = $item['socios']->firstWhere('id', $participacion->id);
            $historial[] = [
                'mes' => $item['periodo']['nombre'],
                'mes_num' => $fecha->month,
                'anio' => $fecha->year,
                'a_cobrar' => $item['a_cobrar'],
                'ingresos' => $item['ingresos'],
                'gastos' => $item['gastos'],
                'falta_cobrar' => $item['falta_cobrar'],
                'utilidad' => $item['utilidad'],
                'mi_participacion' => (float) ($mioMes['liquidacion'] ?? 0),
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
                    'nombre' => $informe['periodo']['nombre'],
                ],
                'resumen' => [
                    'a_cobrar' => $informe['a_cobrar'],
                    'ingresos' => $informe['ingresos'],
                    'gastos' => $informe['gastos'],
                    'falta_cobrar' => $informe['falta_cobrar'],
                    'utilidad' => $informe['utilidad'],
                    'mi_participacion' => (float) ($mio['liquidacion'] ?? 0),
                    'mi_gasto' => (float) ($mio['gastos_proporcional'] ?? 0),
                ],
                'gastos_detalle' => $gastosDetalle,
                'socios' => $socios,
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
