@extends('layouts.app')

@section('title', 'Liquidación ' . $liquidacion->numero . ' - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-calculator me-2"></i>Liquidación {{ $liquidacion->numero }}
    </h1>
    <div class="btn-group">
        @if($liquidacion->estado == 'pendiente')
        <form action="{{ route('liquidaciones.pagar', $liquidacion) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success" onclick="return confirm('¿Marcar como pagada?')">
                <i class="fas fa-check me-1"></i>Marcar Pagada
            </button>
        </form>
        @endif
        <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Información</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Cobrador:</td>
                        <td><strong>{{ $liquidacion->cobrador->nombre }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Período:</td>
                        <td>{{ $liquidacion->fecha_desde->format('d/m/Y') }} - {{ $liquidacion->fecha_hasta->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha Liquidación:</td>
                        <td>{{ $liquidacion->fecha_liquidacion->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Estado:</td>
                        <td>
                            <span class="badge bg-{{ $liquidacion->estado == 'pendiente' ? 'warning' : 'success' }}">
                                {{ ucfirst($liquidacion->estado) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cobros:</td>
                        <td>{{ $liquidacion->cantidad_cobros }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pagos:</td>
                        <td>{{ $liquidacion->cantidad_pagos }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Total Recaudado</small>
                    <h4 class="mb-0">${{ number_format($liquidacion->total_recaudado, 0, ',', '.') }}</h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Comisión ({{ $liquidacion->cobrador->comision_porcentaje }}%)</small>
                    <h4 class="mb-0 text-primary">${{ number_format($liquidacion->total_comision, 0, ',', '.') }}</h4>
                </div>
                <hr>
                <div>
                    <small class="text-muted">A Entregar</small>
                    <h3 class="mb-0 text-success">${{ number_format($liquidacion->total_a_entregar, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-hand-holding-usd me-2"></i>Cobros Incluidos
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Recaudado</th>
                            <th class="text-end">Comisión</th>
                            <th class="text-center">Pagos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($liquidacion->cobros as $cobro)
                        <tr>
                            <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                            <td class="text-end">${{ number_format($cobro->total_recaudado, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($cobro->total_comision, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $cobro->cantidad_pagos }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>TOTAL</td>
                            <td class="text-end">${{ number_format($liquidacion->total_recaudado, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($liquidacion->total_comision, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $liquidacion->cantidad_pagos }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
