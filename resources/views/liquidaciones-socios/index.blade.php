@extends('layouts.app')

@section('title', 'Liquidación de socios - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><i class="fas fa-handshake me-2"></i>Liquidación de socios</h1>
        <small class="text-muted">Cobrado menos gastos, repartido según el % de cada socio</small>
    </div>
    <div class="btn-group">
        <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">Cobradores</a>
        <a href="{{ route('gastos.index', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-primary">
            <i class="fas fa-receipt me-1"></i>Gastos
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('liquidaciones.index') }}">Cobradores</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('liquidaciones.socios') }}">Socios por proyecto</a>
    </li>
</ul>

<div class="card mb-4">
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Ver período</button>
            </div>
        </form>
    </div>
</div>

@php
    $totalCobrado = $informes->sum('ingresos');
    $totalGastos = $informes->sum('gastos');
    $totalUtilidad = $informes->sum('utilidad');
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="stat-label">Cobrado</div>
                <div class="stat-value text-success">${{ number_format($totalCobrado, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="stat-label">Gastos</div>
                <div class="stat-value text-danger">${{ number_format($totalGastos, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-label">A repartir (después de gastos)</div>
                <div class="stat-value">${{ number_format($totalUtilidad, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

@foreach($informes as $informe)
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="badge me-2" style="background-color: {{ $informe['proyecto']->color }};">&nbsp;</span>
            <strong>{{ $informe['proyecto']->nombre }}</strong>
            <small class="text-muted ms-2">{{ $informe['periodo']['nombre'] }}</small>
        </div>
        <a href="{{ route('liquidaciones.socios.show', [$informe['proyecto'], 'mes' => $mes, 'anio' => $anio]) }}" class="btn btn-sm btn-outline-primary">
            Ver informe
        </a>
    </div>
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col-md-3">
                <div class="text-muted small">Cobrado</div>
                <div class="fw-bold text-success">${{ number_format($informe['ingresos'], 0, ',', '.') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Gastos</div>
                <div class="fw-bold text-danger">${{ number_format($informe['gastos'], 0, ',', '.') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">A repartir</div>
                <div class="fw-bold">${{ number_format($informe['utilidad'], 0, ',', '.') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Comisiones cobrador</div>
                <div class="fw-bold text-muted">${{ number_format($informe['comisiones'], 0, ',', '.') }}</div>
            </div>
        </div>

        @if($informe['socios']->isEmpty())
            <div class="alert alert-warning mb-0">
                Este proyecto no tiene socios. Agrégalos en
                <a href="{{ route('proyectos.show', $informe['proyecto']) }}">el proyecto</a>
                (ejemplo: 40% + 40% + 20% o 50% + 50%).
            </div>
        @else
            @if(abs($informe['total_porcentaje'] - 100) > 0.05)
                <div class="alert alert-warning py-2">Los porcentajes suman {{ number_format($informe['total_porcentaje'], 1) }}%, no 100%.</div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th class="text-center">%</th>
                            <th class="text-end">Liquidación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($informe['socios'] as $socio)
                        <tr>
                            <td>{{ $socio['socio'] }}</td>
                            <td class="text-center"><span class="badge bg-info">{{ number_format($socio['porcentaje'], 0) }}%</span></td>
                            <td class="text-end fw-bold">${{ number_format($socio['liquidacion'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endforeach
@endsection
