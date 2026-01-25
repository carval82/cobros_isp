@extends('layouts.app')

@section('title', 'Cobro - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-hand-holding-usd me-2"></i>Cobro del {{ $cobro->fecha->format('d/m/Y') }}
    </h1>
    <div class="btn-group">
        @if($cobro->estado == 'abierto')
        <form action="{{ route('cobros.cerrar', $cobro) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success" onclick="return confirm('¿Cerrar este cobro?')">
                <i class="fas fa-lock me-1"></i>Cerrar Cobro
            </button>
        </form>
        @endif
        <a href="{{ route('cobros.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Información del Cobro</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Cobrador:</td>
                        <td><strong>{{ $cobro->cobrador->nombre }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha:</td>
                        <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Estado:</td>
                        <td>
                            <span class="badge bg-{{ $cobro->estado == 'abierto' ? 'warning' : ($cobro->estado == 'cerrado' ? 'info' : 'success') }}">
                                {{ ucfirst($cobro->estado) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pagos:</td>
                        <td>{{ $cobro->cantidad_pagos }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted mb-1">Recaudado</h6>
                        <h4 class="text-success mb-0">${{ number_format($cobro->total_recaudado, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted mb-1">Comisión</h6>
                        <h4 class="text-primary mb-0">${{ number_format($cobro->total_comision, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-money-bill-wave me-2"></i>Pagos del Cobro
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Recibo</th>
                            <th>Cliente</th>
                            <th>Factura</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cobro->pagos as $pago)
                        <tr>
                            <td><a href="{{ route('pagos.show', $pago) }}">{{ $pago->numero_recibo }}</a></td>
                            <td>{{ $pago->factura->cliente->nombre ?? '-' }}</td>
                            <td>{{ $pago->factura->numero ?? '-' }}</td>
                            <td class="text-end text-success fw-bold">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Sin pagos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
