@extends('layouts.app')

@section('title', 'Clientes - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-users me-2"></i>Clientes
    </h1>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Cliente
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="proyecto_id" class="form-select">
                    <option value="">Todos los proyectos</option>
                    @foreach($proyectos as $proyecto)
                        <option value="{{ $proyecto->id }}" {{ request('proyecto_id') == $proyecto->id ? 'selected' : '' }}>
                            {{ $proyecto->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar nombre, código..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activos</option>
                    <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendidos</option>
                    <option value="cortado" {{ request('estado') == 'cortado' ? 'selected' : '' }}>Cortados</option>
                    <option value="retirado" {{ request('estado') == 'retirado' ? 'selected' : '' }}>Retirados</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de clientes -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Proyecto</th>
                        <th>Dirección</th>
                        <th>Plan</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                    <tr>
                        <td><strong>{{ $cliente->codigo }}</strong></td>
                        <td>
                            <a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre }}</a>
                        </td>
                        <td>
                            @if($cliente->proyecto)
                                <span class="badge" style="background-color: {{ $cliente->proyecto->color }};">{{ $cliente->proyecto->nombre }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($cliente->direccion, 30) }}</td>
                        <td>
                            @if($cliente->servicios->first())
                                {{ $cliente->servicios->first()->planServicio->nombre ?? '-' }}
                            @else
                                <span class="text-muted">Sin servicio</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @switch($cliente->estado)
                                @case('activo')
                                    <span class="badge bg-success">Activo</span>
                                    @break
                                @case('suspendido')
                                    <span class="badge bg-warning">Suspendido</span>
                                    @break
                                @case('cortado')
                                    <span class="badge bg-danger">Cortado</span>
                                    @break
                                @case('retirado')
                                    <span class="badge bg-secondary">Retirado</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            No se encontraron clientes
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($clientes->hasPages())
    <div class="card-footer">
        {{ $clientes->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
