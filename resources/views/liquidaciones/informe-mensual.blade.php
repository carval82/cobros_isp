@extends('layouts.app')

@section('title', 'Informe mensual de cobradores')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-chart-line me-2"></i>Informe mensual de cobradores
    </h1>
    <form action="{{ route('liquidaciones.informe.generar-todos') }}" method="POST" onsubmit="return confirm('¿Generar la liquidación del mes para todos los cobradores? Si ya existe, se deja tal cual.')">
        @csrf
        <input type="hidden" name="mes" value="{{ $mes }}">
        <input type="hidden" name="anio" value="{{ $anio }}">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-invoice-dollar me-1"></i>Liquidar mes completo
        </button>
    </form>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('liquidaciones.index') }}">Cobradores</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('liquidaciones.informe', ['mes' => $mes, 'anio' => $anio]) }}">Proyección e informe</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('liquidaciones.socios') }}">Socios por proyecto</a>
    </li>
</ul>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Mes</label>
                <select name="mes" class="form-select">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ (int)$mes === (int)$num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Año</label>
                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2020">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Ver período</button>
            </div>
        </form>
    </div>
</div>

<p class="text-muted mb-3">
    Proyección según clientes asignados a cada cobrador en <strong>{{ $informe['periodo'] }}</strong>.
    El recaudo y la liquidación quedan con lo cobrado real, aunque no se haya cumplido la meta.
</p>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-label">Proyectado</div>
                <div class="stat-value">${{ number_format($informe['totales']['proyectado'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="stat-label">Recaudado</div>
                <div class="stat-value">${{ number_format($informe['totales']['recaudado'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="stat-label">Pendiente de cartera</div>
                <div class="stat-value">${{ number_format($informe['totales']['pendiente'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <div class="stat-label">Comisiones</div>
                <div class="stat-value">${{ number_format($informe['totales']['comision'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cobrador</th>
                        <th class="text-center">Clientes</th>
                        <th class="text-end">Debe cobrar</th>
                        <th class="text-end">Cobró</th>
                        <th class="text-end">Pendiente</th>
                        <th class="text-center">Cumplimiento</th>
                        <th class="text-end">Comisión</th>
                        <th class="text-end">A entregar</th>
                        <th class="text-center">Liquidación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($informe['cobradores'] as $fila)
                    <tr>
                        <td>
                            <strong>{{ $fila['nombre'] }}</strong>
                            <br><small class="text-muted">{{ $fila['documento'] }} · {{ number_format($fila['comision_porcentaje'], 1) }}%</small>
                        </td>
                        <td class="text-center">{{ $fila['clientes'] }}</td>
                        <td class="text-end">${{ number_format($fila['proyectado'], 0, ',', '.') }}</td>
                        <td class="text-end text-success">${{ number_format($fila['recaudado'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">${{ number_format($fila['pendiente'], 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php $color = $fila['cumplimiento'] >= 90 ? 'success' : ($fila['cumplimiento'] >= 50 ? 'warning' : 'danger'); @endphp
                            <span class="badge bg-{{ $color }}">{{ number_format($fila['cumplimiento'], 1) }}%</span>
                        </td>
                        <td class="text-end">${{ number_format($fila['comision'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">${{ number_format($fila['a_entregar'], 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($fila['liquidacion_id'])
                                <span class="badge bg-{{ $fila['liquidacion_estado'] === 'pagada' ? 'success' : 'warning' }}">
                                    {{ ucfirst($fila['liquidacion_estado']) }}
                                </span>
                            @else
                                <span class="badge bg-secondary">Sin liquidar</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('liquidaciones.informe.cobrador', ['cobrador' => $fila['id'], 'mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-primary" title="Ver cartera">
                                    <i class="fas fa-users"></i>
                                </a>
                                @if($fila['liquidacion_id'])
                                    <a href="{{ route('liquidaciones.show', $fila['liquidacion_id']) }}" class="btn btn-outline-success" title="Ver liquidación">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                @else
                                    <form action="{{ route('liquidaciones.informe.generar') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="cobrador_id" value="{{ $fila['id'] }}">
                                        <input type="hidden" name="mes" value="{{ $mes }}">
                                        <input type="hidden" name="anio" value="{{ $anio }}">
                                        <button type="submit" class="btn btn-outline-secondary" title="Generar liquidación del mes">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No hay cobradores activos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
