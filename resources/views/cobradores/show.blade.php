@extends('layouts.app')

@section('title', $cobrador->nombre . ' - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-tie me-2"></i>{{ $cobrador->nombre }}
    </h1>
    <div class="btn-group">
        <a href="{{ route('cobradores.edit', $cobrador) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ route('cobradores.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
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
                            @if($cobrador->estado == 'activo')
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Documento:</td>
                        <td>{{ $cobrador->documento ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teléfono:</td>
                        <td>{{ $cobrador->telefono ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Celular:</td>
                        <td>{{ $cobrador->celular ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td>{{ $cobrador->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Comisión:</td>
                        <td><strong>{{ number_format($cobrador->comision_porcentaje, 1) }}%</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Recaudado este Mes</h6>
                <h2 class="text-success">${{ number_format($cobrador->totalRecaudadoMes(), 0, ',', '.') }}</h2>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users me-2"></i>Clientes Asignados ({{ $cobrador->clientes->count() }})
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cobrador->clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->codigo }}</td>
                            <td><a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre }}</a></td>
                            <td>{{ Str::limit($cliente->direccion, 30) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $cliente->estado == 'activo' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($cliente->estado) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Sin clientes asignados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-hand-holding-usd me-2"></i>Últimos Cobros
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Recaudado</th>
                            <th class="text-end">Comisión</th>
                            <th class="text-center">Pagos</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cobrador->cobros as $cobro)
                        <tr>
                            <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                            <td class="text-end">${{ number_format($cobro->total_recaudado, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($cobro->total_comision, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $cobro->cantidad_pagos }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $cobro->estado == 'abierto' ? 'warning' : ($cobro->estado == 'cerrado' ? 'info' : 'success') }}">
                                    {{ ucfirst($cobro->estado) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Sin cobros registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
