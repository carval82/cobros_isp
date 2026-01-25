@extends('layouts.app')

@section('title', $proyecto->nombre . ' - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <span class="badge me-2" style="background-color: {{ $proyecto->color }};">&nbsp;</span>
            {{ $proyecto->nombre }}
        </h1>
        <small class="text-muted">{{ $proyecto->codigo }} | {{ $proyecto->ubicacion }}{{ $proyecto->municipio ? ', ' . $proyecto->municipio : '' }}</small>
    </div>
    <div class="btn-group">
        <a href="{{ route('proyectos.edit', $proyecto) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ route('proyectos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-value text-primary">{{ $estadisticas['total_clientes'] }}</div>
                <div class="stat-label">Clientes</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <div class="stat-value text-success">{{ $estadisticas['clientes_activos'] }}</div>
                <div class="stat-label">Activos</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card info">
            <div class="card-body text-center">
                <div class="stat-value text-info">{{ $estadisticas['total_planes'] }}</div>
                <div class="stat-label">Planes</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <div class="stat-value">${{ number_format($estadisticas['facturado_mes'], 0, ',', '.') }}</div>
                <div class="stat-label">Facturado Mes</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <div class="stat-value text-success">${{ number_format($estadisticas['recaudado_mes'], 0, ',', '.') }}</div>
                <div class="stat-label">Recaudado</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card danger">
            <div class="card-body text-center">
                <div class="stat-value text-danger">${{ number_format($estadisticas['pendiente_mes'], 0, ',', '.') }}</div>
                <div class="stat-label">Pendiente</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Planes del Proyecto -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-wifi me-2"></i>Planes</span>
                <a href="{{ route('planes.create', ['proyecto_id' => $proyecto->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($proyecto->planes as $plan)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $plan->nombre }}</strong>
                            <br><small class="text-muted">{{ $plan->velocidad_bajada }}/{{ $plan->velocidad_subida }} Mbps</small>
                        </div>
                        <span class="badge bg-success">${{ number_format($plan->precio, 0, ',', '.') }}</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">Sin planes</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Cobradores -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-user-tie me-2"></i>Cobradores
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($proyecto->cobradores as $cobrador)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $cobrador->nombre }}</span>
                        <span class="badge bg-info">{{ $cobrador->comision_porcentaje }}%</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">Sin cobradores</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Clientes del Proyecto -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Clientes ({{ $proyecto->clientes->count() }})</span>
                <a href="{{ route('clientes.create', ['proyecto_id' => $proyecto->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Nuevo Cliente
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Plan</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proyecto->clientes as $cliente)
                            <tr>
                                <td><strong>{{ $cliente->codigo }}</strong></td>
                                <td>{{ $cliente->nombre }}</td>
                                <td>
                                    @if($cliente->servicios->first())
                                        {{ $cliente->servicios->first()->planServicio->nombre ?? '-' }}
                                    @else
                                        <span class="text-muted">Sin servicio</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $cliente->estado == 'activo' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($cliente->estado) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No hay clientes en este proyecto</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
