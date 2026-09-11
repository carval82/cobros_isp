@extends('layouts.app')

@section('title', 'Cartera de ' . $cobrador->nombre)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-tie me-2"></i>{{ $cobrador->nombre }}
        <small class="text-muted">· {{ $detalle['periodo'] }}</small>
    </h1>
    <a href="{{ route('liquidaciones.informe', ['mes' => $detalle['mes'], 'anio' => $detalle['anio']]) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver al informe
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="stat-label">Debe cobrar</div>
            <div class="stat-value">${{ number_format($detalle['cobrador']['proyectado'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success"><div class="card-body">
            <div class="stat-label">Cobró</div>
            <div class="stat-value">${{ number_format($detalle['cobrador']['recaudado'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning"><div class="card-body">
            <div class="stat-label">Pendiente</div>
            <div class="stat-value">${{ number_format($detalle['cobrador']['pendiente'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info"><div class="card-body">
            <div class="stat-label">Cumplimiento</div>
            <div class="stat-value">{{ number_format($detalle['cobrador']['cumplimiento'], 1) }}%</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Clientes asignados ({{ $detalle['clientes']->count() }})</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th class="text-end">Facturado</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo</th>
                        <th>Estado factura</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalle['clientes'] as $cliente)
                    <tr>
                        <td>
                            <strong>{{ $cliente['nombre'] }}</strong>
                            <br><small class="text-muted">{{ $cliente['codigo'] }}</small>
                        </td>
                        <td>{{ $cliente['documento'] }}</td>
                        <td class="text-end">${{ number_format($cliente['proyectado'], 0, ',', '.') }}</td>
                        <td class="text-end text-success">${{ number_format($cliente['recaudado'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">${{ number_format($cliente['pendiente'], 0, ',', '.') }}</td>
                        <td>
                            @forelse($cliente['facturas'] as $factura)
                                <span class="badge bg-{{ $factura['estado'] === 'pagada' ? 'success' : ($factura['estado'] === 'vencida' ? 'danger' : 'warning') }}">
                                    {{ $factura['numero'] }} · {{ $factura['estado'] }}
                                </span>
                            @empty
                                <span class="text-muted">Sin factura del mes</span>
                            @endforelse
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Este cobrador no tiene clientes asignados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
