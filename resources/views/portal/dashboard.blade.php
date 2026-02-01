@extends('portal.layout')

@section('title', 'Mi Cuenta')

@section('content')
<div class="mb-4">
    <h2 class="text-white mb-1">¡Hola, {{ $cliente->nombre }}!</h2>
    <p class="text-muted">Bienvenido a tu portal de cliente</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card {{ $saldoTotal > 0 ? 'danger' : 'success' }}">
            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-value">${{ number_format($saldoTotal, 0, ',', '.') }}</div>
            <div class="stat-label">Saldo Pendiente</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-value">{{ $facturasPendientes->count() }}</div>
            <div class="stat-label">Facturas Pendientes</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card {{ $ticketsAbiertos > 0 ? '' : 'success' }}">
            <div class="stat-icon">
                <i class="bi bi-chat-dots"></i>
            </div>
            <div class="stat-value">{{ $ticketsAbiertos }}</div>
            <div class="stat-label">Reportes Abiertos</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-portal">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-2"></i>Facturas Pendientes</span>
                <a href="{{ route('portal.estado-cuenta') }}" class="btn btn-sm btn-outline-light">Ver todas</a>
            </div>
            <div class="card-body">
                @if($facturasPendientes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-portal mb-0">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Vencimiento</th>
                                    <th>Total</th>
                                    <th>Saldo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($facturasPendientes as $factura)
                                <tr>
                                    <td>{{ $factura->periodo }}</td>
                                    <td>{{ $factura->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td>${{ number_format($factura->total, 0, ',', '.') }}</td>
                                    <td class="text-warning fw-bold">${{ number_format($factura->saldo, 0, ',', '.') }}</td>
                                    <td>
                                        @if($factura->estado == 'vencida')
                                            <span class="badge bg-danger">Vencida</span>
                                        @elseif($factura->estado == 'parcial')
                                            <span class="badge bg-warning">Parcial</span>
                                        @else
                                            <span class="badge bg-info">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-0">¡Estás al día! No tienes facturas pendientes.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-portal mb-4">
            <div class="card-header">
                <i class="bi bi-wifi me-2"></i>Mi Servicio
            </div>
            <div class="card-body">
                @php $servicio = $cliente->servicioActivo(); @endphp
                @if($servicio)
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="bi bi-router text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 text-white">{{ $servicio->planServicio->nombre ?? 'Plan Básico' }}</h5>
                            <small class="text-muted">
                                {{ $servicio->planServicio->velocidad_bajada ?? '0' }}/{{ $servicio->planServicio->velocidad_subida ?? '0' }} Mbps
                            </small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>Estado:</span>
                        <span class="badge bg-success">Activo</span>
                    </div>
                @else
                    <p class="text-muted mb-0">No hay servicio activo</p>
                @endif
            </div>
        </div>

        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Acciones Rápidas
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('portal.tickets.crear') }}" class="btn btn-portal">
                        <i class="bi bi-plus-circle me-2"></i>Reportar un Problema
                    </a>
                    <a href="{{ route('portal.estado-cuenta') }}" class="btn btn-outline-light">
                        <i class="bi bi-file-text me-2"></i>Ver Estado de Cuenta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($facturasRecientes->count() > 0)
<div class="card card-portal mt-4">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>Últimos Pagos
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-portal mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Fecha Pago</th>
                        <th>Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facturasRecientes as $factura)
                    <tr>
                        <td>{{ $factura->periodo }}</td>
                        <td>{{ $factura->updated_at->format('d/m/Y') }}</td>
                        <td>${{ number_format($factura->total, 0, ',', '.') }}</td>
                        <td><span class="badge bg-success">Pagada</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
