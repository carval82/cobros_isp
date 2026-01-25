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

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
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
                        <td colspan="7" class="text-center py-4 text-muted">
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
