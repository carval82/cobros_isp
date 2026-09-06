@extends('layouts.app')

@section('title', 'Informe ' . $informe['proyecto']->nombre . ' - INTERVEREDANET')

@push('styles')
<style>
    @media print {
        .sidebar, .btn, .no-print, form, .nav-tabs { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>
@endpush

@section('content')
@php $proyecto = $informe['proyecto']; @endphp

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="h3 mb-0">
            <span class="badge me-2" style="background-color: {{ $proyecto->color }};">&nbsp;</span>
            Informe {{ $proyecto->nombre }}
        </h1>
        <small class="text-muted">{{ $informe['periodo']['nombre'] }} · cobrado − gastos = liquidación de cada socio</small>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="{{ route('gastos.index', ['proyecto_id' => $proyecto->id, 'mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-primary">Gastos</a>
        <a href="{{ route('proyectos.show', $proyecto) }}" class="btn btn-outline-secondary">Proyecto</a>
        <a href="{{ route('liquidaciones.socios', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-secondary">Volver</a>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Mes</label>
                <select name="mes" class="form-select">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Año</label>
                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2024" max="2035">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Cambiar período</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <div class="stat-label">Cobrado</div>
                <div class="stat-value text-success">${{ number_format($informe['ingresos'], 0, ',', '.') }}</div>
                <small class="text-muted">{{ $informe['cantidad_pagos'] }} pagos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger h-100">
            <div class="card-body">
                <div class="stat-label">Gastos</div>
                <div class="stat-value text-danger">${{ number_format($informe['gastos'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">A repartir</div>
                <div class="stat-value">${{ number_format($informe['utilidad'], 0, ',', '.') }}</div>
                <small class="text-muted">después de gastos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="stat-label">Comisiones cobrador</div>
                <div class="fw-bold text-muted fs-4">${{ number_format($informe['comisiones'], 0, ',', '.') }}</div>
                <small class="text-muted">se liquidan en Cobradores</small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fas fa-users me-2"></i>Cómo queda cada socio</div>
    <div class="card-body p-0">
        @if($informe['socios']->isEmpty())
            <div class="p-4 text-center text-muted">
                No hay socios en este proyecto.
                <a href="{{ route('proyectos.show', $proyecto) }}">Agregar porcentajes</a>
            </div>
        @else
            @if(abs($informe['total_porcentaje'] - 100) > 0.05)
                <div class="alert alert-warning m-3">Los porcentajes suman {{ number_format($informe['total_porcentaje'], 1) }}%.</div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th>Documento</th>
                            <th class="text-center">%</th>
                            <th class="text-end">Su parte de gastos</th>
                            <th class="text-end">Le corresponde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($informe['socios'] as $socio)
                        <tr>
                            <td class="fw-semibold">{{ $socio['socio'] }}</td>
                            <td class="text-muted">{{ $socio['documento'] ?: '—' }}</td>
                            <td class="text-center"><span class="badge bg-info">{{ number_format($socio['porcentaje'], 0) }}%</span></td>
                            <td class="text-end text-danger">${{ number_format($socio['gastos_proporcional'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold fs-5">${{ number_format($socio['liquidacion'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2">Total</th>
                            <th class="text-center">{{ number_format($informe['total_porcentaje'], 0) }}%</th>
                            <th class="text-end">${{ number_format($informe['gastos'], 0, ',', '.') }}</th>
                            <th class="text-end">${{ number_format($informe['utilidad'], 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2"></i>Gastos del mes</span>
                <a href="{{ route('gastos.index', ['proyecto_id' => $proyecto->id, 'mes' => $mes, 'anio' => $anio]) }}" class="btn btn-sm btn-outline-primary no-print">Registrar</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($informe['gastos_detalle'] as $gasto)
                        <tr>
                            <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                            <td>{{ \App\Models\GastoProyecto::categorias()[$gasto->categoria] ?? $gasto->categoria }}</td>
                            <td>{{ $gasto->descripcion }}</td>
                            <td class="text-end">${{ number_format($gasto->monto, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin gastos este mes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-tie me-2"></i>Comisiones de cobradores</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Cobrador</th>
                            <th class="text-end">Recaudó</th>
                            <th class="text-end">Comisión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($informe['comisiones_detalle'] as $item)
                        <tr>
                            <td>{{ $item['cobrador'] }} <small class="text-muted">({{ number_format($item['porcentaje_comision'], 1) }}%)</small></td>
                            <td class="text-end">${{ number_format($item['recaudado'], 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($item['comision'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Sin cobros este mes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">
                Las comisiones se pagan en Liquidaciones de cobradores. No se restan otra vez del reparto de socios.
            </div>
        </div>
    </div>
</div>
@endsection
