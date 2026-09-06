<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\PlanServicio;
use App\Models\Servicio;
use Carbon\Carbon;

class FacturacionService
{
    /**
     * Cliente nuevo: mes libre.
     *   - Si entra el día 1-15, la primera factura es el mes siguiente (hoy 5 sep → octubre).
     *   - Si entra después del 15, se corre un mes más (16 sep → noviembre).
     * Cliente antiguo: factura del mes en curso.
     */
    public function calcularPrimerPeriodo(string $tipoAlta, ?Carbon $fecha = null): array
    {
        $fecha = ($fecha ?? now())->copy();

        if ($tipoAlta === 'antiguo') {
            return [
                'mes' => (int) $fecha->month,
                'anio' => (int) $fecha->year,
            ];
        }

        $inicio = $fecha->copy()->startOfMonth()->addMonth();
        if ($fecha->day > 15) {
            $inicio->addMonth();
        }

        return [
            'mes' => (int) $inicio->month,
            'anio' => (int) $inicio->year,
        ];
    }

    public function aplicarPeriodoAlta(Cliente $cliente, string $tipoAlta, ?Carbon $fecha = null): Cliente
    {
        $periodo = $this->calcularPrimerPeriodo($tipoAlta, $fecha);

        $cliente->tipo_alta = $tipoAlta;
        $cliente->primer_mes_facturable = $periodo['mes'];
        $cliente->primer_anio_facturable = $periodo['anio'];
        $cliente->save();

        return $cliente;
    }

    /**
     * Corrige un alta mal marcada. Recalcula el mes libre y
     * quita o crea la factura del mes según corresponda.
     */
    public function corregirTipoAlta(Cliente $cliente, string $tipoAlta): array
    {
        $fecha = $cliente->fecha_instalacion
            ? Carbon::parse($cliente->fecha_instalacion)
            : ($cliente->created_at?->copy() ?? now());

        $this->aplicarPeriodoAlta($cliente, $tipoAlta, $fecha);
        $cliente->refresh();

        $ocultas = 0;
        $generadas = 0;

        if ($tipoAlta === 'nuevo') {
            $ocultas = $this->ocultarFacturasAntesDelAlta($cliente);
        }

        if ($tipoAlta === 'antiguo') {
            foreach ($cliente->servicios()->where('estado', 'activo')->get() as $servicio) {
                if ($this->generarFacturaPeriodo($servicio, (int) now()->month, (int) now()->year)) {
                    $generadas++;
                }
            }
        }

        $etiqueta = $cliente->etiquetaPrimeraFactura();
        $mensaje = $tipoAlta === 'nuevo'
            ? 'Quedó como cliente nuevo. Primera factura: ' . $etiqueta . '. Este mes queda libre.'
            : 'Quedó como cliente antiguo. Se genera la factura del mes en curso si tiene plan.';

        if ($ocultas > 0) {
            $mensaje .= " Se quitaron {$ocultas} factura(s) que no correspondían.";
        }
        if ($generadas > 0) {
            $mensaje .= " Se creó la factura de este mes.";
        }

        return [
            'mensaje' => $mensaje,
            'tipo_alta' => $tipoAlta,
            'primera_factura' => $etiqueta,
            'ocultas' => $ocultas,
            'generadas' => $generadas,
        ];
    }

    public function ocultarFacturasAntesDelAlta(Cliente $cliente): int
    {
        $ocultas = 0;
        $facturas = $cliente->facturas()
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->withCount('pagos')
            ->get();

        foreach ($facturas as $factura) {
            if ((int) $factura->pagos_count > 0) {
                continue;
            }
            if ($cliente->puedeFacturarseEn((int) $factura->mes, (int) $factura->anio)) {
                continue;
            }
            $factura->delete();
            $ocultas++;
        }

        return $ocultas;
    }

    public function asignarServicioYFacturar(Cliente $cliente, int $planId, array $extra = []): Servicio
    {
        $plan = PlanServicio::findOrFail($planId);

        $servicio = Servicio::create(array_merge([
            'cliente_id' => $cliente->id,
            'plan_servicio_id' => $plan->id,
            'fecha_inicio' => now(),
            'dia_corte' => 1,
            'dia_pago_limite' => 10,
            'estado' => 'activo',
        ], $extra));

        $this->generarFacturaSiCorrespondeAlAlta($cliente->fresh(), $servicio->fresh(['planServicio', 'cliente']));

        return $servicio;
    }

    public function generarFacturaSiCorrespondeAlAlta(Cliente $cliente, Servicio $servicio): ?Factura
    {
        if ($cliente->tipo_alta !== 'antiguo') {
            return null;
        }

        $mes = (int) now()->month;
        $anio = (int) now()->year;

        return $this->generarFacturaPeriodo($servicio, $mes, $anio);
    }

    public function generarFacturaPeriodo(Servicio $servicio, int $mes, int $anio): ?Factura
    {
        $servicio->loadMissing(['cliente', 'planServicio']);
        $cliente = $servicio->cliente;

        if ($cliente && ! $cliente->puedeFacturarseEn($mes, $anio)) {
            return null;
        }

        if ($servicio->tieneFacturaMes($mes, $anio)) {
            return null;
        }

        $precio = $servicio->precio_mensual;
        $diaLimite = min((int) ($servicio->dia_pago_limite ?: 10), Carbon::create($anio, $mes, 1)->daysInMonth);

        return Factura::create([
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
    }

    public function etiquetaPeriodo(int $mes, int $anio): string
    {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return ($meses[$mes] ?? $mes) . ' ' . $anio;
    }
}
