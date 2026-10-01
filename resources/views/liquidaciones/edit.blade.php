@extends('layouts.app')

@section('title', 'Editar ' . $liquidacion->numero)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-pen me-2"></i>Editar {{ $liquidacion->numero }}
    </h1>
    <a href="{{ route('liquidaciones.show', $liquidacion) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                {{ $liquidacion->cobrador->nombre }}
                · {{ $liquidacion->fecha_desde->format('d/m/Y') }} - {{ $liquidacion->fecha_hasta->format('d/m/Y') }}
            </div>
            <div class="card-body">
                <form action="{{ route('liquidaciones.update', $liquidacion) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Total recaudado</label>
                        <input type="number" name="total_recaudado" class="form-control" min="0" step="1"
                            value="{{ old('total_recaudado', $liquidacion->total_recaudado) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comisión</label>
                        <input type="number" name="total_comision" class="form-control" min="0" step="1"
                            value="{{ old('total_comision', $liquidacion->total_comision) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="4">{{ old('observaciones', $liquidacion->observaciones) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar cambios
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <p class="mb-3">A entregar se calcula como recaudado menos comisión. Si los pagos del período cambiaron, vuelve a calcular antes de editar a mano.</p>
                <form action="{{ route('liquidaciones.recalcular', $liquidacion) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-sync me-1"></i>Recalcular con los pagos del mes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
