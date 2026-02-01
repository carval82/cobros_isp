@extends('portal.layout')

@section('title', 'Reporte #' . $ticket->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('portal.tickets') }}" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-2"></i>Volver a mis reportes
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-portal">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Reporte #{{ $ticket->id }}</span>
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
                    <h4 class="text-white">{{ $ticket->asunto }}</h4>
                </div>

                <div class="bg-dark bg-opacity-50 rounded p-3 mb-4">
                    <p class="mb-0" style="white-space: pre-line;">{{ $ticket->descripcion }}</p>
                </div>

                @if($ticket->respuesta)
                <div class="border-top border-secondary pt-4">
                    <h6 class="text-success mb-3">
                        <i class="bi bi-reply me-2"></i>Respuesta del Equipo
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
    </div>

    <div class="col-lg-4">
        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Información
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de creación</small>
                    <span class="text-white">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
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
                @if($ticket->fecha_respuesta)
                <div>
                    <small class="text-muted d-block">Tiempo de respuesta</small>
                    <span class="text-white">{{ $ticket->created_at->diffForHumans($ticket->fecha_respuesta, true) }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($ticket->estado == 'abierto' || $ticket->estado == 'en_proceso')
        <div class="card card-portal mt-3">
            <div class="card-body text-center">
                <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Tu reporte está siendo atendido. Te notificaremos cuando haya una respuesta.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
