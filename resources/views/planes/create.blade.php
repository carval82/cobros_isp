@extends('layouts.app')

@section('title', 'Nuevo Plan - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle me-2"></i>Nuevo Plan de Servicio
    </h1>
    <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('planes.store') }}" method="POST">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="residencial" {{ old('tipo') == 'residencial' ? 'selected' : '' }}>Residencial</option>
                        <option value="comercial" {{ old('tipo') == 'comercial' ? 'selected' : '' }}>Comercial</option>
                        <option value="empresarial" {{ old('tipo') == 'empresarial' ? 'selected' : '' }}>Empresarial</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Velocidad Bajada (Mbps) *</label>
                    <input type="number" name="velocidad_bajada" class="form-control @error('velocidad_bajada') is-invalid @enderror" value="{{ old('velocidad_bajada') }}" min="1" required>
                    @error('velocidad_bajada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Velocidad Subida (Mbps) *</label>
                    <input type="number" name="velocidad_subida" class="form-control @error('velocidad_subida') is-invalid @enderror" value="{{ old('velocidad_subida') }}" min="1" required>
                    @error('velocidad_subida')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Precio Mensual *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio" class="form-control @error('precio') is-invalid @enderror" value="{{ old('precio') }}" min="0" step="100" required>
                    </div>
                    @error('precio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion') }}" maxlength="255">
                </div>
                
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="activo" class="form-check-input" id="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="activo">Plan activo</label>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
