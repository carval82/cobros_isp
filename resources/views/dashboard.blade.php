@extends('layouts.app')

@section('title', 'Dashboard - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h1>
    <span class="text-muted">{{ now()->format('d/m/Y H:i') }}</span>
</div>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="stat-value">{{ $stats['clientes_activos'] }}</div>
                <div class="stat-label">Clientes Activos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="stat-value">{{ $stats['clientes_suspendidos'] }}</div>
                <div class="stat-label">Clientes Suspendidos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="stat-value">{{ $stats['facturas_pendientes'] }}</div>
                <div class="stat-label">Facturas Pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="stat-value">${{ number_format($stats['saldo_pendiente'], 0, ',', '.') }}</div>
                <div class="stat-label">Saldo por Cobrar</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value">{{ $stats['servicios_activos'] }}</div>
                <div class="stat-label">Servicios Activos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="stat-value">${{ number_format($stats['recaudado_mes'], 0, ',', '.') }}</div>
                <div class="stat-label">Recaudado este Mes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="stat-value">{{ $stats['cobros_abiertos'] }}</div>
                <div class="stat-label">Cobros Abiertos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-value">{{ $stats['cobradores_activos'] }}</div>
                <div class="stat-label">Cobradores Activos</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Facturas Vencidas -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle text-warning me-2"></i>Facturas Vencidas</span>
                <a href="{{ route('facturas.index') }}?estado=vencida" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Periodo</th>
                                <th class="text-end">Saldo</th>
                                <th>Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facturasVencidas as $factura)
                            <tr>
                                <td>
                                    <a href="{{ route('clientes.show', $factura->cliente_id) }}">
                                        {{ $factura->cliente->nombre }}
                                    </a>
                                </td>
                                <td>{{ $factura->periodo }}</td>
                                <td class="text-end text-danger fw-bold">
                                    ${{ number_format($factura->saldo, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="text-danger">
                                        {{ $factura->fecha_vencimiento->format('d/m/Y') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>No hay facturas vencidas
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Pagos -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-money-bill-wave text-success me-2"></i>Últimos Pagos</span>
                <a href="{{ route('pagos.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Recibo</th>
                                <th>Cliente</th>
                                <th class="text-end">Monto</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosPagos as $pago)
                            <tr>
                                <td>{{ $pago->numero_recibo }}</td>
                                <td>{{ $pago->factura->cliente->nombre ?? 'N/A' }}</td>
                                <td class="text-end text-success fw-bold">
                                    ${{ number_format($pago->monto, 0, ',', '.') }}
                                </td>
                                <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    No hay pagos registrados
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cobros Abiertos -->
@if($cobrosAbiertos->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-hand-holding-usd text-primary me-2"></i>Cobros Abiertos
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Cobrador</th>
                                <th>Fecha</th>
                                <th class="text-end">Recaudado</th>
                                <th class="text-center">Pagos</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cobrosAbiertos as $cobro)
                            <tr>
                                <td>{{ $cobro->cobrador->nombre }}</td>
                                <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                                <td class="text-end">${{ number_format($cobro->total_recaudado, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $cobro->cantidad_pagos }}</td>
                                <td class="text-center">
                                    <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
