@extends('layouts.app')

@section('title', 'Nuevo Cobro - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle me-2"></i>Nuevo Cobro
    </h1>
    <a href="{{ route('cobros.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('cobros.store') }}" method="POST">
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
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                    @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('cobros.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Iniciar Cobro</button>
            </div>
        </form>
    </div>
</div>
@endsection
