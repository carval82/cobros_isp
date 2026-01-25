@extends('layouts.app')

@section('title', 'Pago ' . $pago->numero_recibo . ' - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-receipt me-2"></i>Recibo {{ $pago->numero_recibo }}
    </h1>
    <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Información del Pago</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Recibo:</td>
                        <td><strong>{{ $pago->numero_recibo }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha:</td>
                        <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Monto:</td>
                        <td class="text-success fw-bold fs-4">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Método:</td>
                        <td>{{ ucfirst($pago->metodo_pago) }}</td>
                    </tr>
                    @if($pago->referencia_pago)
                    <tr>
                        <td class="text-muted">Referencia:</td>
                        <td>{{ $pago->referencia_pago }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Cobrador:</td>
                        <td>{{ $pago->cobrador->nombre ?? 'Oficina' }}</td>
                    </tr>
                    @if($pago->cobro)
                    <tr>
                        <td class="text-muted">Cobro:</td>
                        <td><a href="{{ route('cobros.show', $pago->cobro) }}">{{ $pago->cobro->fecha->format('d/m/Y') }}</a></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Factura Asociada</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Factura:</td>
                        <td><a href="{{ route('facturas.show', $pago->factura) }}">{{ $pago->factura->numero }}</a></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cliente:</td>
                        <td><a href="{{ route('clientes.show', $pago->factura->cliente) }}">{{ $pago->factura->cliente->nombre }}</a></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Periodo:</td>
                        <td>{{ $pago->factura->periodo }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Factura:</td>
                        <td>${{ number_format($pago->factura->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Saldo Actual:</td>
                        <td class="{{ $pago->factura->saldo > 0 ? 'text-danger' : 'text-success' }} fw-bold">
                            ${{ number_format($pago->factura->saldo, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
