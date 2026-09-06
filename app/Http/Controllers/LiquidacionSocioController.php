<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Services\LiquidacionProyectoService;
use Illuminate\Http\Request;

class LiquidacionSocioController extends Controller
{
    public function __construct(private LiquidacionProyectoService $service)
    {
    }

    public function index(Request $request)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $informes = $this->service->resumenTodos($mes, $anio);

        return view('liquidaciones-socios.index', [
            'informes' => $informes,
            'mes' => $mes,
            'anio' => $anio,
            'meses' => LiquidacionProyectoService::meses(),
        ]);
    }

    public function show(Request $request, Proyecto $proyecto)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $informe = $this->service->calcular($proyecto, $mes, $anio);

        return view('liquidaciones-socios.show', [
            'informe' => $informe,
            'mes' => $mes,
            'anio' => $anio,
            'meses' => LiquidacionProyectoService::meses(),
        ]);
    }
}
