@extends('layouts.app')

@section('title', 'Proyectos - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-project-diagram me-2"></i>Proyectos / Sectores
    </h1>
    <a href="{{ route('proyectos.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Proyecto
    </a>
</div>

<div class="row g-4">
    @forelse($proyectos as $proyecto)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100" style="border-left: 4px solid {{ $proyecto->color }};">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="card-title mb-1">{{ $proyecto->nombre }}</h5>
                        <small class="text-muted">{{ $proyecto->codigo }}</small>
                    </div>
                    <span class="badge bg-{{ $proyecto->activo ? 'success' : 'secondary' }}">
                        {{ $proyecto->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                
                @if($proyecto->descripcion)
                <p class="text-muted small mb-3">{{ $proyecto->descripcion }}</p>
                @endif

                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="fw-bold text-primary fs-4">{{ $proyecto->clientes_count }}</div>
                        <small class="text-muted">Clientes</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-success fs-4">{{ $proyecto->planes_count }}</div>
                        <small class="text-muted">Planes</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-info fs-4">{{ $proyecto->cobradores_asignados_count }}</div>
                        <small class="text-muted">Cobradores</small>
                    </div>
                </div>

                @if($proyecto->ubicacion || $proyecto->municipio)
                <p class="small text-muted mb-2">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    {{ $proyecto->ubicacion }}{{ $proyecto->municipio ? ', ' . $proyecto->municipio : '' }}
                </p>
                @endif
            </div>
            <div class="card-footer bg-transparent">
                <div class="btn-group btn-group-sm w-100">
                    <a href="{{ route('proyectos.show', $proyecto) }}" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Ver
                    </a>
                    <a href="{{ route('proyectos.edit', $proyecto) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <a href="{{ route('clientes.index', ['proyecto_id' => $proyecto->id]) }}" class="btn btn-outline-success">
                        <i class="fas fa-users me-1"></i>Clientes
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay proyectos registrados</h5>
                <p class="text-muted">Crea tu primer proyecto para organizar clientes por sectores</p>
                <a href="{{ route('proyectos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Crear Proyecto
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
