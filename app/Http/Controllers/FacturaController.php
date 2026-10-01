<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Servicio;
use App\Models\Proyecto;
use App\Services\LiquidacionProyectoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $datos = $this->consultaFacturas($request);
        $facturas = $datos['query']
            ->orderBy('facturas.anio', 'desc')
            ->orderBy('facturas.mes', 'desc')
            ->orderBy('facturas.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('facturas.index', [
            'facturas' => $facturas,
            'proyectos' => $datos['proyectos'],
            'meses' => $datos['meses'],
            'resumen' => $datos['resumen'],
            'consulta' => $datos['consulta'],
        ]);
    }

    public function exportarExcel(Request $request)
    {
        $datos = $this->consultaFacturas($request);
        $facturas = $this->facturasParaExportar($datos['query']);
        $titulo = $this->tituloExportacion($request, $datos);
        $xml = $this->excelFacturas($titulo, $facturas, $datos['consulta'], $datos['resumen']);
        $nombre = $this->nombreArchivo($request, $datos, 'xls');

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $datos = $this->consultaFacturas($request);
        $facturas = $this->facturasParaExportar($datos['query']);
        $titulo = $this->tituloExportacion($request, $datos);
        $pdf = Pdf::loadView('facturas.pdf.listado', [
            'titulo' => $titulo,
            'facturas' => $facturas,
            'consulta' => $datos['consulta'],
            'resumen' => $datos['resumen'],
        ])->setPaper('letter', 'landscape');

        return $pdf->download($this->nombreArchivo($request, $datos, 'pdf'));
    }

    private function consultaFacturas(Request $request): array
    {
        $pagadoEnMes = \App\Services\AtribucionPago::sqlPagadoEnPeriodoFactura();

        $query = Factura::with(['cliente.proyecto', 'servicio.planServicio'])
            ->select('facturas.*')
            ->selectRaw("{$pagadoEnMes} as pagado_en_mes");

        if ($request->filled('mes') && $request->filled('anio')) {
            $query->where('facturas.mes', $request->mes)->where('facturas.anio', $request->anio);
        }

        if ($request->filled('proyecto_id')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('proyecto_id', $request->proyecto_id);
            });
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        $consulta = $request->filled('mes') && $request->filled('anio');
        $resumen = null;
        if ($consulta) {
            $faltantes = (clone $query)
                ->where('facturas.estado', '!=', 'anulada')
                ->whereRaw("facturas.total > {$pagadoEnMes}");
            $resumen = [
                'facturas' => (clone $query)->count(),
                'faltantes' => (clone $faltantes)->count(),
                'saldo' => (float) (clone $faltantes)->reorder()->select(DB::raw("coalesce(sum(facturas.total - {$pagadoEnMes}), 0) as saldo_mes"))->value('saldo_mes'),
            ];
        }

        if ($request->input('estado') === 'sin_pago') {
            $query->where('facturas.estado', '!=', 'anulada')
                ->whereRaw("facturas.total > {$pagadoEnMes}");
        } elseif ($request->filled('estado')) {
            $query->where('facturas.estado', $request->estado);
        }

        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        $meses = LiquidacionProyectoService::meses();

        return compact('query', 'consulta', 'resumen', 'proyectos', 'meses');
    }

    private function facturasParaExportar($query)
    {
        return $query->get()
            ->sortBy(fn ($factura) => mb_strtolower($factura->cliente->nombre ?? ''))
            ->values();
    }

    private function tituloExportacion(Request $request, array $datos): string
    {
        $partes = ['Facturas'];
        if ($request->filled('proyecto_id')) {
            $partes[] = $datos['proyectos']->firstWhere('id', (int) $request->proyecto_id)?->nombre ?? 'Proyecto';
        }
        if ($datos['consulta']) {
            $partes[] = ($datos['meses'][(int) $request->mes] ?? $request->mes).' '.$request->anio;
        }
        if ($request->input('estado') === 'sin_pago') {
            $partes[] = 'sin pago en el mes';
        }

        return implode(' · ', $partes);
    }

    private function nombreArchivo(Request $request, array $datos, string $extension): string
    {
        $partes = ['facturas'];
        if ($request->filled('proyecto_id')) {
            $nombre = $datos['proyectos']->firstWhere('id', (int) $request->proyecto_id)?->nombre ?? 'proyecto';
            $partes[] = str($nombre)->slug('_');
        }
        if ($datos['consulta']) {
            $partes[] = $request->anio.'-'.str_pad((string) $request->mes, 2, '0', STR_PAD_LEFT);
        }
        if ($request->input('estado') === 'sin_pago') {
            $partes[] = 'sin-pago';
        }

        return implode('-', $partes).'.'.$extension;
    }

    private function excelFacturas(string $titulo, $facturas, bool $consulta, ?array $resumen): string
    {
        $filas = [
            [$titulo],
            [],
        ];
        if ($resumen) {
            $filas[] = ['Faltan por pago', $resumen['faltantes'], 'de', $resumen['facturas'], 'Saldo del mes', $resumen['saldo']];
            $filas[] = [];
        }
        $filas[] = ['Número', 'Cliente', 'Documento', 'Celular', 'Proyecto', 'Periodo', 'Total', $consulta ? 'Saldo del mes' : 'Saldo', 'Estado'];

        foreach ($facturas as $factura) {
            $saldo = $consulta
                ? max(0, (float) $factura->total - (float) $factura->pagado_en_mes)
                : (float) $factura->saldo;
            $filas[] = [
                $factura->numeroMostrar(),
                $factura->cliente->nombre ?? '',
                $factura->cliente->documento ?? '',
                $factura->cliente->celular ?: ($factura->cliente->telefono ?? ''),
                $factura->cliente->proyecto->nombre ?? 'Sin proyecto',
                $factura->periodo,
                (float) $factura->total,
                $saldo,
                ucfirst($factura->estado),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Worksheet ss:Name="Facturas"><Table>';

        foreach ($filas as $fila) {
            $xml .= '<Row>';
            foreach ($fila as $valor) {
                $tipo = is_numeric($valor) && ! is_string($valor) ? 'Number' : 'String';
                $texto = htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<Cell><Data ss:Type="'.$tipo.'">'.$texto.'</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        return $xml.'</Table></Worksheet></Workbook>';
    }

    public function create()
    {
        $servicios = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->whereHas('cliente', fn ($q) => $q->where('estado', '!=', 'retirado'))
            ->get();
        return view('facturas.create', compact('servicios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'servicio_id' => 'required|exists:servicios,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
            'subtotal' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'concepto' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $servicio = Servicio::with('cliente')->find($validated['servicio_id']);
        
        $descuento = $validated['descuento'] ?? 0;
        $total = $validated['subtotal'] - $descuento;

        $factura = Factura::create([
            'cliente_id' => $servicio->cliente_id,
            'servicio_id' => $validated['servicio_id'],
            'mes' => $validated['mes'],
            'anio' => $validated['anio'],
            'fecha_emision' => now(),
            'fecha_vencimiento' => Carbon::create($validated['anio'], $validated['mes'], $servicio->dia_pago_limite),
            'subtotal' => $validated['subtotal'],
            'descuento' => $descuento,
            'total' => $total,
            'saldo' => $total,
            'concepto' => $validated['concepto'],
            'notas' => $validated['notas'],
        ]);

        return redirect()->route('facturas.show', $factura)
            ->with('success', 'Factura creada correctamente');
    }

    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'servicio.planServicio', 'pagos.cobrador']);
        return view('facturas.show', compact('factura'));
    }

    public function edit(Factura $factura)
    {
        return view('facturas.edit', compact('factura'));
    }

    public function update(Request $request, Factura $factura)
    {
        $validated = $request->validate([
            'descuento' => 'nullable|numeric|min:0',
            'recargo' => 'nullable|numeric|min:0',
            'concepto' => 'nullable|string',
            'notas' => 'nullable|string',
            'estado' => 'required|in:pendiente,pagada,parcial,vencida,anulada',
        ]);

        $total = $factura->subtotal - ($validated['descuento'] ?? 0) + ($validated['recargo'] ?? 0);
        $validated['total'] = $total;

        if ($validated['estado'] == 'anulada') {
            $validated['saldo'] = 0;
        }

        $factura->update($validated);

        return redirect()->route('facturas.show', array_filter([
            'factura' => $factura,
            'return' => \App\Support\ListReturn::isSafe($request->input('return')) ? $request->input('return') : null,
        ]))->with('success', 'Factura actualizada correctamente');
    }

    public function destroy(Factura $factura)
    {
        if ($factura->pagos()->exists()) {
            return back()->with('error', 'No se puede eliminar la factura porque tiene pagos asociados');
        }

        $factura->delete();

        return redirect()->route('facturas.index')
            ->with('success', 'Factura eliminada correctamente');
    }

    public function generarMes(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
        ]);

        $servicios = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->whereHas('cliente', fn ($q) => $q->where('estado', '!=', 'retirado'))
            ->get();

        $generadas = 0;
        $omitidas = 0;

        $facturacion = app(\App\Services\FacturacionService::class);
        foreach ($servicios as $servicio) {
            $factura = $facturacion->generarFacturaPeriodo($servicio, (int) $validated['mes'], (int) $validated['anio']);
            if ($factura) {
                $generadas++;
            } else {
                $omitidas++;
            }
        }

        return redirect()->route('facturas.index', ['mes' => $validated['mes'], 'anio' => $validated['anio']])
            ->with('success', "Se generaron {$generadas} facturas. {$omitidas} omitidas (ya existían o mes libre).");
    }

    public function pdf(Factura $factura)
    {
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);
        
        $pdf = Pdf::loadView('facturas.pdf.factura', compact('factura'))
            ->setOption('isRemoteEnabled', true);
        
        return $pdf->stream("factura-{$factura->numero}.pdf");
    }

    public function descargarPdf(Factura $factura)
    {
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);
        
        $pdf = Pdf::loadView('facturas.pdf.factura', compact('factura'))
            ->setOption('isRemoteEnabled', true);
        
        return $pdf->download("factura-{$factura->numero}.pdf");
    }

    public function enviarWhatsapp(Factura $factura)
    {
        $url = $factura->urlWhatsApp();
        if (! $url) {
            return back()->with('error', 'El cliente no tiene celular para enviar por WhatsApp.');
        }

        $factura->update(['enviada_whatsapp_at' => now()]);

        return redirect()->away($url);
    }

    public function causarAlegra(Factura $factura)
    {
        $resultado = app(\App\Services\AlegraService::class)->causarFactura($factura);

        return back()->with($resultado['ok'] ? 'success' : 'error', $resultado['message']);
    }

    public function generarMesProyecto(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
            'proyecto_id' => 'nullable|exists:proyectos,id',
        ]);

        $query = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->whereHas('cliente', fn ($q) => $q->where('estado', '!=', 'retirado'));

        if ($request->filled('proyecto_id')) {
            $query->whereHas('cliente', function($q) use ($validated) {
                $q->where('proyecto_id', $validated['proyecto_id']);
            });
        }

        $servicios = $query->get();

        $generadas = 0;
        $omitidas = 0;

        $facturacion = app(\App\Services\FacturacionService::class);
        foreach ($servicios as $servicio) {
            $factura = $facturacion->generarFacturaPeriodo($servicio, (int) $validated['mes'], (int) $validated['anio']);
            if ($factura) {
                $generadas++;
            } else {
                $omitidas++;
            }
        }

        $proyectoNombre = $request->filled('proyecto_id') 
            ? Proyecto::find($validated['proyecto_id'])->nombre 
            : 'Todos los proyectos';

        return redirect()->route('facturas.index', ['mes' => $validated['mes'], 'anio' => $validated['anio']])
            ->with('success', "{$proyectoNombre}: Se generaron {$generadas} facturas. {$omitidas} omitidas.");
    }

    public function resetSeptiembre()
    {
        $mes = 9;
        $anio = (int) now()->year;

        $eliminadas = Factura::query()->count();
        Factura::query()->delete();

        $resultado = $this->generarFacturasActivas($mes, $anio);

        return redirect()->route('facturas.index', ['mes' => $mes, 'anio' => $anio])
            ->with('success', "Estados de cuenta limpios: se ocultaron {$eliminadas} facturas anteriores y se cargaron {$resultado['generadas']} de septiembre {$anio}.");
    }

    private function generarFacturasActivas(int $mes, int $anio, ?int $proyectoId = null): array
    {
        $query = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo')
            ->whereHas('cliente', fn ($q) => $q->where('estado', '!=', 'retirado'));

        if ($proyectoId) {
            $query->whereHas('cliente', function ($q) use ($proyectoId) {
                $q->where('proyecto_id', $proyectoId);
            });
        }

        $generadas = 0;
        $omitidas = 0;

        foreach ($query->get() as $servicio) {
            if ($servicio->cliente && ! $servicio->cliente->puedeFacturarseEn($mes, $anio)) {
                $omitidas++;
                continue;
            }

            if ($servicio->tieneFacturaMes($mes, $anio)) {
                $omitidas++;
                continue;
            }

            $precio = $servicio->precio_mensual;
            $diaLimite = min((int) ($servicio->dia_pago_limite ?: 10), Carbon::create($anio, $mes, 1)->daysInMonth);

            Factura::create([
                'cliente_id' => $servicio->cliente_id,
                'servicio_id' => $servicio->id,
                'mes' => $mes,
                'anio' => $anio,
                'fecha_emision' => Carbon::create($anio, $mes, 1),
                'fecha_vencimiento' => Carbon::create($anio, $mes, $diaLimite),
                'subtotal' => $precio,
                'total' => $precio,
                'saldo' => $precio,
                'concepto' => 'Servicio de Internet - ' . ($servicio->planServicio->nombre ?? 'Plan'),
            ]);

            $generadas++;
        }

        return compact('generadas', 'omitidas');
    }
}
