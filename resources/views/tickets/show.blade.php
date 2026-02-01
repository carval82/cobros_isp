@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('tickets.index') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-2"></i>Volver a tickets
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Ticket #{{ $ticket->id }}</span>
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
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
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
                        <small class="text-muted">{{ $ticket->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                    <h4>{{ $ticket->asunto }}</h4>
                </div>

                <div class="bg-light rounded p-3 mb-4">
                    <p class="mb-0" style="white-space: pre-line;">{{ $ticket->descripcion }}</p>
                </div>

                @if($ticket->respuesta)
                <div class="border-top pt-4">
                    <h6 class="text-success mb-3">
                        <i class="bi bi-reply me-2"></i>Respuesta
                    </h6>
                    <div class="bg-success bg-opacity-10 border border-success rounded p-3">
                        <p class="mb-2" style="white-space: pre-line;">{{ $ticket->respuesta }}</p>
                        @if($ticket->fecha_respuesta)
                        <small class="text-muted">
                            Respondido el {{ $ticket->fecha_respuesta->format('d/m/Y H:i') }}
                            @if($ticket->atendidoPor)
                                por {{ $ticket->atendidoPor->name }}
                            @endif
                        </small>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-reply me-2"></i>Responder al Cliente
            </div>
            <div class="card-body">
                <form action="{{ route('tickets.responder', $ticket) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Respuesta</label>
                        <textarea name="respuesta" class="form-control" rows="4" required>{{ old('respuesta', $ticket->respuesta) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cambiar Estado</label>
                        <select name="estado" class="form-select">
                            <option value="en_proceso" {{ $ticket->estado == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="resuelto" {{ $ticket->estado == 'resuelto' ? 'selected' : '' }}>Resuelto</option>
                            <option value="cerrado" {{ $ticket->estado == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Enviar Respuesta
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Cliente
            </div>
            <div class="card-body">
                <h5 class="mb-1">{{ $ticket->cliente->nombre }}</h5>
                <p class="text-muted mb-2">{{ $ticket->cliente->codigo }}</p>
                
                <div class="mb-2">
                    <small class="text-muted d-block">Documento</small>
                    {{ $ticket->cliente->documento }}
                </div>

                @if($ticket->cliente->celular)
                <div class="mb-2">
                    <small class="text-muted d-block">Celular</small>
                    <a href="tel:{{ $ticket->cliente->celular }}">{{ $ticket->cliente->celular }}</a>
                </div>
                @endif

                <div class="mb-2">
                    <small class="text-muted d-block">Dirección</small>
                    {{ $ticket->cliente->direccion }}
                </div>

                <a href="{{ route('clientes.show', $ticket->cliente) }}" class="btn btn-sm btn-outline-primary mt-2">
                    Ver Cliente
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Información
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Proyecto</small>
                    {{ $ticket->proyecto->nombre ?? 'N/A' }}
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Prioridad</small>
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
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Creado</small>
                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                </div>
                @if($ticket->fecha_respuesta)
                <div>
                    <small class="text-muted d-block">Respondido</small>
                    {{ $ticket->fecha_respuesta->format('d/m/Y H:i') }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear me-2"></i>Acciones
            </div>
            <div class="card-body">
                <form action="{{ route('tickets.estado', $ticket) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <select name="estado" class="form-select form-select-sm">
                        <option value="abierto" {{ $ticket->estado == 'abierto' ? 'selected' : '' }}>Abierto</option>
                        <option value="en_proceso" {{ $ticket->estado == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                        <option value="resuelto" {{ $ticket->estado == 'resuelto' ? 'selected' : '' }}>Resuelto</option>
                        <option value="cerrado" {{ $ticket->estado == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cambiar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
