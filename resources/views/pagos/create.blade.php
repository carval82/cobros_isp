@extends('layouts.app')

@section('title', 'Nuevo Pago - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-dollar-sign me-2"></i>Registrar Pago
    </h1>
    <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('pagos.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Factura *</label>
                    <select name="factura_id" class="form-select @error('factura_id') is-invalid @enderror" required id="selectFactura">
                        <option value="">Seleccionar factura</option>
                        @foreach($facturas as $factura)
                            <option value="{{ $factura->id }}" 
                                data-saldo="{{ $factura->saldo }}"
                                {{ ($facturaSeleccionada && $facturaSeleccionada->id == $factura->id) ? 'selected' : '' }}>
                                {{ $factura->numero }} - {{ $factura->cliente->nombre }} - {{ $factura->periodo }} (Saldo: ${{ number_format($factura->saldo, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    @error('factura_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Monto *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="monto" class="form-control @error('monto') is-invalid @enderror" 
                            value="{{ old('monto', $facturaSeleccionada->saldo ?? '') }}" min="1" required id="inputMonto">
                    </div>
                    @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha de Pago *</label>
                    <input type="date" name="fecha_pago" class="form-control @error('fecha_pago') is-invalid @enderror" 
                        value="{{ old('fecha_pago', now()->format('Y-m-d')) }}" required>
                    @error('fecha_pago')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Método de Pago *</label>
                    <select name="metodo_pago" class="form-select" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="nequi">Nequi</option>
                        <option value="daviplata">Daviplata</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Referencia</label>
                    <input type="text" name="referencia_pago" class="form-control" value="{{ old('referencia_pago') }}" placeholder="Nº transferencia">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Cobrador</label>
                    <select name="cobrador_id" class="form-select">
                        <option value="">Oficina (sin cobrador)</option>
                        @foreach($cobradores as $cobrador)
                            <option value="{{ $cobrador->id }}">{{ $cobrador->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Cobro Asociado</label>
                    <select name="cobro_id" class="form-select">
                        <option value="">Sin asociar a cobro</option>
                        @foreach($cobrosAbiertos as $cobro)
                            <option value="{{ $cobro->id }}">{{ $cobro->fecha->format('d/m/Y') }} - {{ $cobro->cobrador->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2">{{ old('notas') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Registrar Pago</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectFactura').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const saldo = option.dataset.saldo;
    if (saldo) {
        document.getElementById('inputMonto').value = saldo;
    }
});
</script>
@endpush
