@extends('layouts.app')

@section('title', 'Editar Servicio - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-edit me-2"></i>Editar Servicio
    </h1>
    <a href="{{ route('clientes.show', $servicio->cliente_id) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('servicios.update', $servicio) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" value="{{ $servicio->cliente->nombre }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Plan *</label>
                    <select name="plan_servicio_id" class="form-select" required>
                        @foreach($planes as $plan)
                            <option value="{{ $plan->id }}" {{ $servicio->plan_servicio_id == $plan->id ? 'selected' : '' }}>
                                {{ $plan->nombre }} ({{ $plan->velocidad_bajada }}/{{ $plan->velocidad_subida }} Mbps) - ${{ number_format($plan->precio, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">IP Asignada</label>
                    <input type="text" name="ip_asignada" class="form-control" value="{{ old('ip_asignada', $servicio->ip_asignada) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-control" value="{{ old('mac_address', $servicio->mac_address) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo Equipo</label>
                    <input type="text" name="equipo_modelo" class="form-control" value="{{ old('equipo_modelo', $servicio->equipo_modelo) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Serial Equipo</label>
                    <input type="text" name="equipo_serial" class="form-control" value="{{ old('equipo_serial', $servicio->equipo_serial) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Día de Corte *</label>
                    <input type="number" name="dia_corte" class="form-control" value="{{ old('dia_corte', $servicio->dia_corte) }}" min="1" max="28" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Día Límite Pago *</label>
                    <input type="number" name="dia_pago_limite" class="form-control" value="{{ old('dia_pago_limite', $servicio->dia_pago_limite) }}" min="1" max="28" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Precio Especial</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio_especial" class="form-control" value="{{ old('precio_especial', $servicio->precio_especial) }}" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado *</label>
                    <select name="estado" class="form-select" required>
                        <option value="activo" {{ $servicio->estado == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="suspendido" {{ $servicio->estado == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                        <option value="cortado" {{ $servicio->estado == 'cortado' ? 'selected' : '' }}>Cortado</option>
                        <option value="cancelado" {{ $servicio->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas', $servicio->notas) }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('clientes.show', $servicio->cliente_id) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
