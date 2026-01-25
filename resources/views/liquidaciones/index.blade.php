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

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
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
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
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
                        <td>{{ $liquidacion->cobrador->nombre }}</td>
                        <td>{{ $liquidacion->fecha_desde->format('d/m') }} - {{ $liquidacion->fecha_hasta->format('d/m/Y') }}</td>
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
                                <a href="{{ route('liquidaciones.show', $liquidacion) }}" class="btn btn-outline-primary"><i class="fas fa-eye"></i></a>
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
                    <tr><td colspan="8" class="text-center py-4 text-muted">No hay liquidaciones</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($liquidaciones->hasPages())<div class="card-footer">{{ $liquidaciones->withQueryString()->links() }}</div>@endif
</div>
@endsection
