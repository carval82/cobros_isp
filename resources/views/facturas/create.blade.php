@extends('layouts.app')

@section('title', 'Nueva Factura - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-file-invoice-dollar me-2"></i>Nueva Factura
    </h1>
    <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('facturas.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Servicio (Cliente) *</label>
                    <select name="servicio_id" class="form-select @error('servicio_id') is-invalid @enderror" required id="selectServicio">
                        <option value="">Seleccionar servicio</option>
                        @foreach($servicios as $servicio)
                            <option value="{{ $servicio->id }}" 
                                data-precio="{{ $servicio->precio_mensual }}"
                                {{ old('servicio_id') == $servicio->id ? 'selected' : '' }}>
                                {{ $servicio->cliente->codigo }} - {{ $servicio->cliente->nombre }} 
                                ({{ $servicio->planServicio->nombre }} - ${{ number_format($servicio->precio_mensual, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    @error('servicio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Mes *</label>
                    <select name="mes" class="form-select" required>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ old('mes', now()->month) == $i ? 'selected' : '' }}>
                                {{ $i }} - {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Año *</label>
                    <select name="anio" class="form-select" required>
                        @for($i = now()->year; $i >= 2020; $i--)
                            <option value="{{ $i }}" {{ old('anio', now()->year) == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Subtotal *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="subtotal" class="form-control @error('subtotal') is-invalid @enderror" 
                            value="{{ old('subtotal') }}" min="0" required id="inputSubtotal">
                    </div>
                    @error('subtotal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Descuento</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="descuento" class="form-control" value="{{ old('descuento', 0) }}" min="0">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Recargo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="recargo" class="form-control" value="{{ old('recargo', 0) }}" min="0">
                    </div>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="concepto" class="form-control" value="{{ old('concepto', 'Servicio de Internet') }}">
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Crear Factura</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectServicio').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const precio = option.dataset.precio;
    if (precio) {
        document.getElementById('inputSubtotal').value = precio;
    }
});
</script>
@endpush
