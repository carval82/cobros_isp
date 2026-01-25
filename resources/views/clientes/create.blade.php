@extends('layouts.app')

@section('title', 'Nuevo Cliente - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
    </h1>
    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tipo Documento</label>
                    <select name="tipo_documento" class="form-select">
                        <option value="CC" {{ old('tipo_documento') == 'CC' ? 'selected' : '' }}>CC</option>
                        <option value="NIT" {{ old('tipo_documento') == 'NIT' ? 'selected' : '' }}>NIT</option>
                        <option value="CE" {{ old('tipo_documento') == 'CE' ? 'selected' : '' }}>CE</option>
                        <option value="TI" {{ old('tipo_documento') == 'TI' ? 'selected' : '' }}>TI</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" value="{{ old('documento') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                
                <div class="col-md-8">
                    <label class="form-label">Dirección *</label>
                    <input type="text" name="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion') }}" required>
                    @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Barrio</label>
                    <input type="text" name="barrio" class="form-control" value="{{ old('barrio') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Municipio</label>
                    <input type="text" name="municipio" class="form-control" value="{{ old('municipio', 'Villamaría') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Departamento</label>
                    <input type="text" name="departamento" class="form-control" value="{{ old('departamento', 'Caldas') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Cobrador Asignado</label>
                    <select name="cobrador_id" class="form-select">
                        <option value="">Sin asignar</option>
                        @foreach($cobradores as $cobrador)
                            <option value="{{ $cobrador->id }}" {{ old('cobrador_id') == $cobrador->id ? 'selected' : '' }}>
                                {{ $cobrador->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha de Instalación</label>
                    <input type="date" name="fecha_instalacion" class="form-control" value="{{ old('fecha_instalacion') }}">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Referencia de Ubicación</label>
                    <textarea name="referencia_ubicacion" class="form-control" rows="2">{{ old('referencia_ubicacion') }}</textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas') }}</textarea>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
