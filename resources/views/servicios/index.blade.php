@extends('layouts.app')

@section('title', 'Servicios - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-network-wired me-2"></i>Servicios
    </h1>
    <a href="{{ route('servicios.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Servicio
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="plan_id" class="form-select">
                    <option value="">Todos los planes</option>
                    @foreach($planes as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                    <option value="cortado" {{ request('estado') == 'cortado' ? 'selected' : '' }}>Cortado</option>
                    <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th>Cliente</th>
                        <th>Plan</th>
                        <th>IP</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Día Corte</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($servicios as $servicio)
                    <tr>
                        <td>
                            <a href="{{ route('clientes.show', $servicio->cliente_id) }}">{{ $servicio->cliente->nombre }}</a>
                            <br><small class="text-muted">{{ $servicio->cliente->codigo }}</small>
                        </td>
                        <td>
                            {{ $servicio->planServicio->nombre }}
                            <br><small class="text-muted">{{ $servicio->planServicio->velocidad_bajada }}/{{ $servicio->planServicio->velocidad_subida }} Mbps</small>
                        </td>
                        <td>{{ $servicio->ip_asignada ?: '-' }}</td>
                        <td class="text-end">${{ number_format($servicio->precio_mensual, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $servicio->dia_corte }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $servicio->estado == 'activo' ? 'success' : 'secondary' }}">
                                {{ ucfirst($servicio->estado) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay servicios</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($servicios->hasPages())<div class="card-footer">{{ $servicios->withQueryString()->links() }}</div>@endif
</div>
@endsection
