<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Cobro;
use App\Models\Servicio;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        [$mes, $anio] = \App\Services\AtribucionPago::mesGestion();

        $stats = [
            'clientes_activos' => Cliente::where('estado', 'activo')->count(),
            'clientes_suspendidos' => Cliente::where('estado', 'suspendido')->count(),
            'servicios_activos' => Servicio::where('estado', 'activo')->count(),
            'facturas_pendientes' => Factura::whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count(),
            'saldo_pendiente' => Factura::whereIn('estado', ['pendiente', 'parcial', 'vencida'])->sum('saldo'),
            'recaudado_mes' => \App\Services\AtribucionPago::aplicar(Pago::query(), $mes, $anio)->sum('monto'),
            'cobros_abiertos' => Cobro::where('estado', 'abierto')->count(),
            'cobradores_activos' => Cobrador::where('estado', 'activo')->count(),
        ];

        $facturasVencidas = Factura::with(['cliente', 'servicio.planServicio'])
            ->where('estado', 'vencida')
            ->orWhere(function ($q) {
                $q->where('estado', 'pendiente')
                  ->where('fecha_vencimiento', '<', now());
            })
            ->orderBy('fecha_vencimiento')
            ->limit(10)
            ->get();

        $ultimosPagos = Pago::with(['factura.cliente', 'cobrador'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $cobrosAbiertos = Cobro::with('cobrador')
            ->where('estado', 'abierto')
            ->orderBy('fecha', 'desc')
            ->get();

        $meses = \App\Services\LiquidacionProyectoService::meses();
        $nombreMes = ($meses[$mes] ?? $mes).' '.$anio;

        return view('dashboard', compact('stats', 'facturasVencidas', 'ultimosPagos', 'cobrosAbiertos', 'nombreMes'));
    }
}
