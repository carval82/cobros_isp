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
                <div class="col-12">
                    <label class="form-label">Tipo de alta *</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="border rounded p-3 d-block h-100 {{ old('tipo_alta', 'nuevo') == 'nuevo' ? 'border-success' : '' }}" style="cursor:pointer">
                                <input type="radio" name="tipo_alta" value="nuevo" class="form-check-input me-2" {{ old('tipo_alta', 'nuevo') == 'nuevo' ? 'checked' : '' }} required>
                                <strong>Cliente nuevo</strong>
                                <div class="small text-muted mt-1">Queda un mes libre. Si entra hoy (día 1-15) la primera factura es {{ $facturacion->etiquetaPeriodo($periodoNuevo['mes'], $periodoNuevo['anio']) }}. Si entra después del 15, se corre un mes más.</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="border rounded p-3 d-block h-100 {{ old('tipo_alta') == 'antiguo' ? 'border-primary' : '' }}" style="cursor:pointer">
                                <input type="radio" name="tipo_alta" value="antiguo" class="form-check-input me-2" {{ old('tipo_alta') == 'antiguo' ? 'checked' : '' }}>
                                <strong>Cliente antiguo</strong>
                                <div class="small text-muted mt-1">Ya venía del servicio. Se genera automáticamente la factura de {{ $facturacion->etiquetaPeriodo($periodoAntiguo['mes'], $periodoAntiguo['anio']) }} si le asignas un plan.</div>
                            </label>
                        </div>
                    </div>
                    @error('tipo_alta')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Plan (opcional)</label>
                    <select name="plan_servicio_id" class="form-select">
                        <option value="">Asignar después</option>
                        @foreach($planes as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_servicio_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->nombre }} - ${{ number_format($plan->precio, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Si eliges plan, se crea el servicio y la factura según el tipo de alta.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proyecto *</label>
                    <select name="proyecto_id" class="form-select @error('proyecto_id') is-invalid @enderror" required>
                        <option value="">Seleccionar proyecto</option>
                        @foreach($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}" {{ (old('proyecto_id', $proyectoSeleccionado) == $proyecto->id) ? 'selected' : '' }}>
                                {{ $proyecto->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('proyecto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                @include('clientes.partials.datos-tributarios', ['cliente' => $cliente ?? null])
                
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular') }}">
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
