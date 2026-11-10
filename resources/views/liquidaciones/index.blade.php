@extends('layouts.app')

@section('title', 'Liquidaciones - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-calculator me-2"></i>Liquidaciones
    </h1>
    <a href="{{ route('liquidaciones.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nueva Liquidación
    </a>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('liquidaciones.index', ['mes' => $mes, 'anio' => $anio]) }}">Cobradores</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('liquidaciones.informe', ['mes' => $mes, 'anio' => $anio]) }}">Proyección e informe</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('liquidaciones.socios', ['mes' => $mes, 'anio' => $anio]) }}">Socios por proyecto</a>
    </li>
</ul>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <select name="mes" class="form-select">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ (int) $mes === (int) $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2020">
            </div>
            <div class="col-md-3">
                <select name="cobrador_id" class="form-select">
                    <option value="">Todos los cobradores</option>
                    @foreach($cobradores as $cobrador)
                        <option value="{{ $cobrador->id }}" {{ request('cobrador_id') == $cobrador->id ? 'selected' : '' }}>
                            {{ $cobrador->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pagada" {{ request('estado') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Ver mes</button>
                <a href="{{ route('liquidaciones.index', ['todas' => 1]) }}" class="btn btn-outline-secondary">Todas</a>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">Hasta el día {{ \App\Services\AtribucionPago::DIAS_GRACIA }} se muestra {{ $meses[(int) $mes] ?? $mes }} {{ $anio }}. Si aún no se generó la liquidación del cobrador, abajo aparece lo cobrado de ese mes.</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cobrador</th>
                        <th>Período</th>
                        <th class="text-end">Recaudado</th>
                        <th class="text-end">Comisión</th>
                        <th class="text-end">A Entregar</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($liquidaciones as $liquidacion)
                    <tr>
                        <td><strong>{{ $liquidacion->numero }}</strong></td>
                        <td>{{ $liquidacion->cobrador->nombre ?? 'Cobrador eliminado' }}</td>
                        <td>{{ $liquidacion->fecha_desde?->format('d/m') }} - {{ $liquidacion->fecha_hasta?->format('d/m/Y') }}</td>
                        <td class="text-end">${{ number_format($liquidacion->total_recaudado, 0, ',', '.') }}</td>
                        <td class="text-end text-primary">${{ number_format($liquidacion->total_comision, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">${{ number_format($liquidacion->total_a_entregar, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $liquidacion->estado == 'pendiente' ? 'warning' : 'success' }}">
                                {{ ucfirst($liquidacion->estado) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('liquidaciones.show', $liquidacion) }}" class="btn btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('liquidaciones.edit', $liquidacion) }}" class="btn btn-outline-secondary" title="Editar"><i class="fas fa-pen"></i></a>
                                @if($liquidacion->estado == 'pendiente')
                                <form action="{{ route('liquidaciones.pagar', $liquidacion) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success" title="Marcar pagada" onclick="return confirm('¿Marcar como pagada?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No hay liquidaciones guardadas de {{ $meses[(int) $mes] ?? $mes }} {{ $anio }}.
                            <a href="{{ route('liquidaciones.informe', ['mes' => $mes, 'anio' => $anio]) }}">Ver la proyección de ese mes</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($liquidaciones->hasPages())<div class="card-footer">{{ $liquidaciones->withQueryString()->links() }}</div>@endif
</div>

@if(!$verTodas && $informe)
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Cobrado en {{ $informe['periodo'] }}</span>
        <a href="{{ route('liquidaciones.informe', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-sm btn-outline-primary">Ver informe completo</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cobrador</th>
                        <th class="text-end">Cobró</th>
                        <th class="text-end">Pendiente</th>
                        <th class="text-end">Comisión</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($informe['cobradores'] as $fila)
                    <tr>
                        <td>{{ $fila['nombre'] }}</td>
                        <td class="text-end text-success">${{ number_format((float) ($fila['recaudado'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end text-danger">${{ number_format((float) ($fila['pendiente'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">${{ number_format((float) ($fila['comision'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($fila['liquidacion_id'])
                                <a href="{{ route('liquidaciones.show', $fila['liquidacion_id']) }}" class="badge bg-{{ ($fila['liquidacion_estado'] ?? '') === 'pagada' ? 'success' : 'warning' }} text-decoration-none">
                                    {{ ucfirst((string) ($fila['liquidacion_estado'] ?? 'pendiente')) }}
                                </a>
                            @else
                                <span class="badge bg-secondary">Sin generar</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">No hay cobradores activos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
