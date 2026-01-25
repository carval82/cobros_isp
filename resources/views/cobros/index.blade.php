@extends('layouts.app')

@section('title', 'Cobros - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-hand-holding-usd me-2"></i>Cobros
    </h1>
    <a href="{{ route('cobros.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Cobro
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="cobrador_id" class="form-select">
                    <option value="">Todos los cobradores</option>
                    @foreach($cobradores as $cobrador)
                        <option value="{{ $cobrador->id }}" {{ request('cobrador_id') == $cobrador->id ? 'selected' : '' }}>
                            {{ $cobrador->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    <option value="abierto" {{ request('estado') == 'abierto' ? 'selected' : '' }}>Abierto</option>
                    <option value="cerrado" {{ request('estado') == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                    <option value="liquidado" {{ request('estado') == 'liquidado' ? 'selected' : '' }}>Liquidado</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha" class="form-control" value="{{ request('fecha') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('cobros.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cobrador</th>
                        <th class="text-end">Recaudado</th>
                        <th class="text-end">Comisión</th>
                        <th class="text-center">Pagos</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cobros as $cobro)
                    <tr>
                        <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                        <td>{{ $cobro->cobrador->nombre }}</td>
                        <td class="text-end">${{ number_format($cobro->total_recaudado, 0, ',', '.') }}</td>
                        <td class="text-end">${{ number_format($cobro->total_comision, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $cobro->cantidad_pagos }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $cobro->estado == 'abierto' ? 'warning' : ($cobro->estado == 'cerrado' ? 'info' : 'success') }}">
                                {{ ucfirst($cobro->estado) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('cobros.show', $cobro) }}" class="btn btn-outline-primary"><i class="fas fa-eye"></i></a>
                                @if($cobro->estado == 'abierto')
                                <form action="{{ route('cobros.cerrar', $cobro) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Cerrar este cobro?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success" title="Cerrar"><i class="fas fa-lock"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay cobros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cobros->hasPages())<div class="card-footer">{{ $cobros->withQueryString()->links() }}</div>@endif
</div>
@endsection
