@extends('layouts.app')

@section('title', 'Tickets de Soporte')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Tickets de Soporte</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="abierto" {{ request('estado') == 'abierto' ? 'selected' : '' }}>Abierto</option>
                    <option value="en_proceso" {{ request('estado') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                    <option value="resuelto" {{ request('estado') == 'resuelto' ? 'selected' : '' }}>Resuelto</option>
                    <option value="cerrado" {{ request('estado') == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="daño" {{ request('tipo') == 'daño' ? 'selected' : '' }}>Daño</option>
                    <option value="cobro" {{ request('tipo') == 'cobro' ? 'selected' : '' }}>Cobro</option>
                    <option value="soporte" {{ request('tipo') == 'soporte' ? 'selected' : '' }}>Soporte</option>
                    <option value="otro" {{ request('tipo') == 'otro' ? 'selected' : '' }}>Otro</option>
                </select>
            </div>
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
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Proyecto</th>
                    <th>Tipo</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td>
                        <a href="{{ route('clientes.show', $ticket->cliente_id) }}">
                            {{ $ticket->cliente->nombre }}
                        </a>
                    </td>
                    <td>{{ $ticket->proyecto->nombre ?? '-' }}</td>
                    <td>
                        @switch($ticket->tipo)
                            @case('daño')
                                <span class="badge bg-danger">Daño</span>
                                @break
                            @case('cobro')
                                <span class="badge bg-primary">Cobro</span>
                                @break
                            @case('soporte')
                                <span class="badge bg-info">Soporte</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Otro</span>
                        @endswitch
                    </td>
                    <td>{{ Str::limit($ticket->asunto, 30) }}</td>
                    <td>
                        @switch($ticket->estado)
                            @case('abierto')
                                <span class="badge bg-warning">Abierto</span>
                                @break
                            @case('en_proceso')
                                <span class="badge bg-info">En Proceso</span>
                                @break
                            @case('resuelto')
                                <span class="badge bg-success">Resuelto</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Cerrado</span>
                        @endswitch
                    </td>
                    <td>
                        @switch($ticket->prioridad)
                            @case('urgente')
                                <span class="badge bg-danger">Urgente</span>
                                @break
                            @case('alta')
                                <span class="badge bg-warning">Alta</span>
                                @break
                            @case('media')
                                <span class="badge bg-info">Media</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Baja</span>
                        @endswitch
                    </td>
                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        No hay tickets registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $tickets->withQueryString()->links() }}
</div>
@endsection
