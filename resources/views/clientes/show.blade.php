@extends('layouts.app')

@section('title', $cliente->nombre . ' - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user me-2"></i>{{ $cliente->nombre }}
        <small class="text-muted">({{ $cliente->codigo }})</small>
    </h1>
    <div class="btn-group">
        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Información del Cliente -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i>Información
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Estado:</td>
                        <td>
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
                    </tr>
                    <tr>
                        <td class="text-muted">Documento:</td>
                        <td>{{ $cliente->tipo_documento }} {{ $cliente->documento ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teléfono:</td>
                        <td>{{ $cliente->telefono ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Celular:</td>
                        <td>{{ $cliente->celular ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td>{{ $cliente->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dirección:</td>
                        <td>{{ $cliente->direccion }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Barrio:</td>
                        <td>{{ $cliente->barrio ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Municipio:</td>
                        <td>{{ $cliente->municipio }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cobrador:</td>
                        <td>{{ $cliente->cobrador->nombre ?? 'Sin asignar' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Instalación:</td>
                        <td>{{ $cliente->fecha_instalacion?->format('d/m/Y') ?: '-' }}</td>
                    </tr>
                </table>
                
                @if($cliente->referencia_ubicacion)
                <hr>
                <p class="mb-0"><strong>Referencia:</strong><br>{{ $cliente->referencia_ubicacion }}</p>
                @endif
                
                @if($cliente->notas)
                <hr>
                <p class="mb-0"><strong>Notas:</strong><br>{{ $cliente->notas }}</p>
                @endif
            </div>
        </div>

        <!-- Saldo Pendiente -->
        <div class="card mt-3">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Saldo Pendiente</h6>
                <h2 class="{{ $cliente->saldoPendiente() > 0 ? 'text-danger' : 'text-success' }}">
                    ${{ number_format($cliente->saldoPendiente(), 0, ',', '.') }}
                </h2>
            </div>
        </div>
    </div>

    <!-- Servicios y Facturas -->
    <div class="col-lg-8">
        <!-- Servicios -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-network-wired me-2"></i>Servicios</span>
                <a href="{{ route('servicios.create') }}?cliente_id={{ $cliente->id }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Agregar
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Precio</th>
                            <th>Día Corte</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cliente->servicios as $servicio)
                        <tr>
                            <td>
                                <strong>{{ $servicio->planServicio->nombre }}</strong>
                                <br><small class="text-muted">{{ $servicio->planServicio->velocidad_bajada }}/{{ $servicio->planServicio->velocidad_subida }} Mbps</small>
                            </td>
                            <td>${{ number_format($servicio->precio_mensual, 0, ',', '.') }}</td>
                            <td>Día {{ $servicio->dia_corte }}</td>
                            <td class="text-center">
                                @if($servicio->estado == 'activo')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($servicio->estado) }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Sin servicios registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Facturas -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-invoice-dollar me-2"></i>Historial de Facturas
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Periodo</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cliente->facturas as $factura)
                        <tr>
                            <td>
                                <a href="{{ route('facturas.show', $factura) }}">{{ $factura->numero }}</a>
                            </td>
                            <td>{{ $factura->periodo }}</td>
                            <td class="text-end">${{ number_format($factura->total, 0, ',', '.') }}</td>
                            <td class="text-end {{ $factura->saldo > 0 ? 'text-danger fw-bold' : '' }}">
                                ${{ number_format($factura->saldo, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @switch($factura->estado)
                                    @case('pagada')
                                        <span class="badge bg-success">Pagada</span>
                                        @break
                                    @case('pendiente')
                                        <span class="badge bg-warning">Pendiente</span>
                                        @break
                                    @case('parcial')
                                        <span class="badge bg-info">Parcial</span>
                                        @break
                                    @case('vencida')
                                        <span class="badge bg-danger">Vencida</span>
                                        @break
                                    @case('anulada')
                                        <span class="badge bg-secondary">Anulada</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Sin facturas registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
