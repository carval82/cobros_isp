@extends('layouts.app')

@section('title', 'Nuevo Cobrador - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-plus me-2"></i>Nuevo Cobrador
    </h1>
    <a href="{{ route('cobradores.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('cobradores.store') }}" method="POST">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Proyecto</label>
                    <select name="proyecto_id" class="form-select">
                        <option value="">Sin proyecto</option>
                        @foreach($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}" {{ old('proyecto_id') == $proyecto->id ? 'selected' : '' }}>
                                {{ $proyecto->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Comisión (%) *</label>
                    <input type="number" name="comision_porcentaje" class="form-control @error('comision_porcentaje') is-invalid @enderror" value="{{ old('comision_porcentaje', 5) }}" min="0" max="100" step="0.5" required>
                    @error('comision_porcentaje')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Documento (Cédula) *</label>
                    <input type="text" name="documento" class="form-control @error('documento') is-invalid @enderror" value="{{ old('documento') }}" required>
                    @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Se usa para login en la app</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">PIN App *</label>
                    <input type="password" name="pin" class="form-control @error('pin') is-invalid @enderror" minlength="4" maxlength="6" required placeholder="4-6 dígitos">
                    @error('pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">PIN para acceso a la app móvil</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('cobradores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar Cobrador
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
