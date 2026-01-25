@extends('layouts.app')

@section('title', 'Nuevo Proyecto - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle me-2"></i>Nuevo Proyecto
    </h1>
    <a href="{{ route('proyectos.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('proyectos.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Código *</label>
                    <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror" 
                        value="{{ old('codigo') }}" required placeholder="Ej: JOYA, REM">
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" 
                        value="{{ old('nombre') }}" required placeholder="Ej: La Joya, Remigio">
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" 
                        value="{{ old('color', '#0ea5e9') }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" 
                        value="{{ old('descripcion') }}" placeholder="Descripción breve del proyecto">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control" 
                        value="{{ old('ubicacion') }}" placeholder="Vereda, sector o zona">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Municipio</label>
                    <input type="text" name="municipio" class="form-control" 
                        value="{{ old('municipio') }}" placeholder="Municipio">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('proyectos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Proyecto</button>
            </div>
        </form>
    </div>
</div>
@endsection
