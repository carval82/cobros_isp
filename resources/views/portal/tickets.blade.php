@extends('portal.layout')

@section('title', 'Mis Reportes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white mb-1">Mis Reportes</h2>
        <p class="text-muted mb-0">Historial de solicitudes y reportes</p>
    </div>
    <a href="{{ route('portal.tickets.crear') }}" class="btn btn-portal">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Reporte
    </a>
</div>

<div class="card card-portal">
    <div class="card-body">
        @if($tickets->count() > 0)
            <div class="table-responsive">
                <table class="table table-portal">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo</th>
                            <th>Asunto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
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
                            <td>{{ Str::limit($ticket->asunto, 40) }}</td>
                            <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
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
                            <td class="text-end">
                                <a href="{{ route('portal.tickets.ver', $ticket->id) }}" class="btn btn-sm btn-outline-light">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $tickets->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-chat-square-text text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 mb-3">No tienes reportes registrados</p>
                <a href="{{ route('portal.tickets.crear') }}" class="btn btn-portal">
                    <i class="bi bi-plus-circle me-2"></i>Crear mi primer reporte
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
