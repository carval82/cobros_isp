@extends('layouts.app')

@section('title', 'Editar Factura - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-edit me-2"></i>Editar Factura {{ $factura->numero }}
    </h1>
    <a href="{{ route('facturas.show', $factura) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('facturas.update', $factura) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" value="{{ $factura->cliente->nombre }}" disabled>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Periodo</label>
                    <input type="text" class="form-control" value="{{ $factura->periodo }}" disabled>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="pendiente" {{ $factura->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="pagada" {{ $factura->estado == 'pagada' ? 'selected' : '' }}>Pagada</option>
                        <option value="parcial" {{ $factura->estado == 'parcial' ? 'selected' : '' }}>Parcial</option>
                        <option value="vencida" {{ $factura->estado == 'vencida' ? 'selected' : '' }}>Vencida</option>
                        <option value="anulada" {{ $factura->estado == 'anulada' ? 'selected' : '' }}>Anulada</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Subtotal *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="subtotal" class="form-control" 
                            value="{{ old('subtotal', $factura->subtotal) }}" min="0" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Descuento</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="descuento" class="form-control" 
                            value="{{ old('descuento', $factura->descuento) }}" min="0">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Recargo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="recargo" class="form-control" 
                            value="{{ old('recargo', $factura->recargo) }}" min="0">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" class="form-control" 
                        value="{{ old('fecha_vencimiento', $factura->fecha_vencimiento->format('Y-m-d')) }}">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="concepto" class="form-control" 
                        value="{{ old('concepto', $factura->concepto) }}">
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('facturas.show', $factura) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
