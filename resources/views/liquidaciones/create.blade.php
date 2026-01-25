@extends('layouts.app')

@section('title', 'Nueva Liquidación - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-calculator me-2"></i>Nueva Liquidación
    </h1>
    <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('liquidaciones.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cobrador *</label>
                    <select name="cobrador_id" class="form-select @error('cobrador_id') is-invalid @enderror" required>
                        <option value="">Seleccionar cobrador</option>
                        @foreach($cobradores as $cobrador)
                            <option value="{{ $cobrador->id }}">{{ $cobrador->nombre }} ({{ $cobrador->comision_porcentaje }}%)</option>
                        @endforeach
                    </select>
                    @error('cobrador_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Solo se muestran cobradores con cobros cerrados sin liquidar</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde *</label>
                    <input type="date" name="fecha_desde" class="form-control @error('fecha_desde') is-invalid @enderror" 
                        value="{{ old('fecha_desde', now()->startOfMonth()->format('Y-m-d')) }}" required>
                    @error('fecha_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta *</label>
                    <input type="date" name="fecha_hasta" class="form-control @error('fecha_hasta') is-invalid @enderror" 
                        value="{{ old('fecha_hasta', now()->format('Y-m-d')) }}" required>
                    @error('fecha_hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Se liquidarán todos los cobros <strong>cerrados</strong> del cobrador seleccionado en el período indicado que no hayan sido liquidados previamente.
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Crear Liquidación</button>
            </div>
        </form>
    </div>
</div>
@endsection
