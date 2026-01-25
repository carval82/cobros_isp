@extends('layouts.app')

@section('title', 'Editar Cliente - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-edit me-2"></i>Editar Cliente
    </h1>
    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('clientes.update', $cliente) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Proyecto *</label>
                    <select name="proyecto_id" class="form-select">
                        <option value="">Sin proyecto</option>
                        @foreach($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}" {{ old('proyecto_id', $cliente->proyecto_id) == $proyecto->id ? 'selected' : '' }}>
                                {{ $proyecto->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $cliente->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tipo Documento</label>
                    <select name="tipo_documento" class="form-select">
                        <option value="CC" {{ old('tipo_documento', $cliente->tipo_documento) == 'CC' ? 'selected' : '' }}>CC</option>
                        <option value="NIT" {{ old('tipo_documento', $cliente->tipo_documento) == 'NIT' ? 'selected' : '' }}>NIT</option>
                        <option value="CE" {{ old('tipo_documento', $cliente->tipo_documento) == 'CE' ? 'selected' : '' }}>CE</option>
                        <option value="TI" {{ old('tipo_documento', $cliente->tipo_documento) == 'TI' ? 'selected' : '' }}>TI</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Documento</label>
                    <input type="text" name="documento" class="form-control" value="{{ old('documento', $cliente->documento) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular', $cliente->celular) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $cliente->email) }}">
                </div>
                
                <div class="col-md-8">
                    <label class="form-label">Dirección *</label>
                    <input type="text" name="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion', $cliente->direccion) }}" required>
                    @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Barrio</label>
                    <input type="text" name="barrio" class="form-control" value="{{ old('barrio', $cliente->barrio) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Municipio</label>
                    <input type="text" name="municipio" class="form-control" value="{{ old('municipio', $cliente->municipio) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Departamento</label>
                    <input type="text" name="departamento" class="form-control" value="{{ old('departamento', $cliente->departamento) }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="activo" {{ old('estado', $cliente->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="suspendido" {{ old('estado', $cliente->estado) == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                        <option value="cortado" {{ old('estado', $cliente->estado) == 'cortado' ? 'selected' : '' }}>Cortado</option>
                        <option value="retirado" {{ old('estado', $cliente->estado) == 'retirado' ? 'selected' : '' }}>Retirado</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Cobrador Asignado</label>
                    <select name="cobrador_id" class="form-select">
                        <option value="">Sin asignar</option>
                        @foreach($cobradores as $cobrador)
                            <option value="{{ $cobrador->id }}" {{ old('cobrador_id', $cliente->cobrador_id) == $cobrador->id ? 'selected' : '' }}>
                                {{ $cobrador->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha de Instalación</label>
                    <input type="date" name="fecha_instalacion" class="form-control" value="{{ old('fecha_instalacion', $cliente->fecha_instalacion?->format('Y-m-d')) }}">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Referencia de Ubicación</label>
                    <textarea name="referencia_ubicacion" class="form-control" rows="2">{{ old('referencia_ubicacion', $cliente->referencia_ubicacion) }}</textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas', $cliente->notas) }}</textarea>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
