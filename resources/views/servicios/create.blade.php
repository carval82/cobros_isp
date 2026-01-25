@extends('layouts.app')

@section('title', 'Nuevo Servicio - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle me-2"></i>Nuevo Servicio
    </h1>
    <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('servicios.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente *</label>
                    <select name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                        <option value="">Seleccionar cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ ($clienteSeleccionado && $clienteSeleccionado->id == $cliente->id) ? 'selected' : '' }}>
                                {{ $cliente->codigo }} - {{ $cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Plan *</label>
                    <select name="plan_servicio_id" class="form-select @error('plan_servicio_id') is-invalid @enderror" required>
                        <option value="">Seleccionar plan</option>
                        @foreach($planes as $plan)
                            <option value="{{ $plan->id }}">
                                {{ $plan->nombre }} ({{ $plan->velocidad_bajada }}/{{ $plan->velocidad_subida }} Mbps) - ${{ number_format($plan->precio, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @error('plan_servicio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">IP Asignada</label>
                    <input type="text" name="ip_asignada" class="form-control" value="{{ old('ip_asignada') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-control" value="{{ old('mac_address') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo Equipo</label>
                    <input type="text" name="equipo_modelo" class="form-control" value="{{ old('equipo_modelo') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Serial Equipo</label>
                    <input type="text" name="equipo_serial" class="form-control" value="{{ old('equipo_serial') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Día de Corte *</label>
                    <input type="number" name="dia_corte" class="form-control" value="{{ old('dia_corte', 1) }}" min="1" max="28" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Día Límite Pago *</label>
                    <input type="number" name="dia_pago_limite" class="form-control" value="{{ old('dia_pago_limite', 10) }}" min="1" max="28" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Precio Especial</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio_especial" class="form-control" value="{{ old('precio_especial') }}" min="0">
                    </div>
                    <small class="text-muted">Dejar vacío para usar precio del plan</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('servicios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Servicio</button>
            </div>
        </form>
    </div>
</div>
@endsection
