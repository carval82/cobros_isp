@extends('layouts.app')

@section('title', 'Planes de Servicio - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-box me-2"></i>Planes de Servicio
    </h1>
    <a href="{{ route('planes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Plan
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Velocidad</th>
                        <th class="text-end">Precio</th>
                        <th>Tipo</th>
                        <th class="text-center">Servicios</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planes as $plan)
                    <tr>
                        <td>
                            <strong>{{ $plan->nombre }}</strong>
                            @if($plan->descripcion)
                                <br><small class="text-muted">{{ $plan->descripcion }}</small>
                            @endif
                        </td>
                        <td>
                            <i class="fas fa-arrow-down text-success"></i> {{ $plan->velocidad_bajada }} Mbps
                            <br>
                            <i class="fas fa-arrow-up text-primary"></i> {{ $plan->velocidad_subida }} Mbps
                        </td>
                        <td class="text-end fw-bold">${{ number_format($plan->precio, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $plan->tipo == 'residencial' ? 'info' : ($plan->tipo == 'comercial' ? 'warning' : 'primary') }}">
                                {{ ucfirst($plan->tipo) }}
                            </span>
                        </td>
                        <td class="text-center">{{ $plan->servicios_count }}</td>
                        <td class="text-center">
                            @if($plan->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('planes.edit', $plan) }}" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('planes.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-box fa-2x mb-2 d-block"></i>
                            No hay planes registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
