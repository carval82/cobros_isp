@extends('layouts.app')

@section('title', 'Cobradores - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-tie me-2"></i>Cobradores
    </h1>
    <a href="{{ route('cobradores.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Cobrador
    </a>
</div>

<!-- Filtro por Proyecto -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('cobradores.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="col-form-label"><i class="fas fa-filter me-1"></i>Filtrar por Proyecto:</label>
            </div>
            <div class="col-auto">
                <select name="proyecto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los proyectos --</option>
                    @foreach($proyectos as $proyecto)
                        <option value="{{ $proyecto->id }}" {{ request('proyecto_id') == $proyecto->id ? 'selected' : '' }}>
                            {{ $proyecto->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('proyecto_id'))
            <div class="col-auto">
                <a href="{{ route('cobradores.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Proyectos</th>
                        <th>Contacto</th>
                        <th class="text-center">Comisión</th>
                        <th class="text-center">Clientes</th>
                        <th class="text-center">Cobros</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cobradores as $cobrador)
                    <tr>
                        <td>
                            <a href="{{ route('cobradores.show', $cobrador) }}">
                                <strong>{{ $cobrador->nombre }}</strong>
                            </a>
                            @if($cobrador->documento)
                                <br><small class="text-muted">{{ $cobrador->documento }}</small>
                            @endif
                        </td>
                        <td>
                            @forelse($cobrador->proyectos as $proyecto)
                                <span class="badge" style="background-color: {{ $proyecto->color ?? '#6c757d' }}">
                                    {{ $proyecto->nombre }}
                                </span>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                        <td>
                            @if($cobrador->celular)
                                <i class="fas fa-mobile-alt text-muted"></i> {{ $cobrador->celular }}
                            @elseif($cobrador->telefono)
                                <i class="fas fa-phone text-muted"></i> {{ $cobrador->telefono }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($cobrador->comision_porcentaje, 1) }}%</td>
                        <td class="text-center">{{ $cobrador->clientes_count }}</td>
                        <td class="text-center">{{ $cobrador->cobros_count }}</td>
                        <td class="text-center">
                            @if($cobrador->estado == 'activo')
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('cobradores.show', $cobrador) }}" class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('cobradores.edit', $cobrador) }}" class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-user-tie fa-2x mb-2 d-block"></i>
                            No hay cobradores registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
