@extends('layouts.app')

@section('title', 'Factura ' . $factura->numero . ' - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-file-invoice-dollar me-2"></i>Factura {{ $factura->numero }}
    </h1>
    <div class="btn-group">
        @if($factura->saldo > 0 && $factura->estado !== 'anulada')
        <a href="{{ route('pagos.create', ['factura_id' => $factura->id]) }}" class="btn btn-success">
            <i class="fas fa-dollar-sign me-1"></i>Registrar Pago
        </a>
        @endif
        <a href="{{ route('facturas.edit', $factura) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Información de la Factura</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Cliente:</td>
                        <td><a href="{{ route('clientes.show', $factura->cliente_id) }}">{{ $factura->cliente->nombre }}</a></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Código:</td>
                        <td>{{ $factura->cliente->codigo }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Plan:</td>
                        <td>{{ $factura->servicio->planServicio->nombre ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Periodo:</td>
                        <td><strong>{{ $factura->periodo }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha Emisión:</td>
                        <td>{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha Vencimiento:</td>
                        <td class="{{ $factura->estaVencida() ? 'text-danger' : '' }}">
                            {{ $factura->fecha_vencimiento->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Estado:</td>
                        <td>
                            @switch($factura->estado)
                                @case('pagada')<span class="badge bg-success">Pagada</span>@break
                                @case('pendiente')<span class="badge bg-warning">Pendiente</span>@break
                                @case('parcial')<span class="badge bg-info">Parcial</span>@break
                                @case('vencida')<span class="badge bg-danger">Vencida</span>@break
                                @case('anulada')<span class="badge bg-secondary">Anulada</span>@break
                            @endswitch
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Detalle de Valores</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-end">${{ number_format($factura->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($factura->descuento > 0)
                    <tr class="text-success">
                        <td>Descuento:</td>
                        <td class="text-end">-${{ number_format($factura->descuento, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($factura->recargo > 0)
                    <tr class="text-danger">
                        <td>Recargo:</td>
                        <td class="text-end">+${{ number_format($factura->recargo, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="fw-bold border-top">
                        <td>Total:</td>
                        <td class="text-end">${{ number_format($factura->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Pagado:</td>
                        <td class="text-end text-success">${{ number_format($factura->total - $factura->saldo, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold {{ $factura->saldo > 0 ? 'text-danger' : 'text-success' }}">
                        <td>Saldo:</td>
                        <td class="text-end">${{ number_format($factura->saldo, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-money-bill-wave me-2"></i>Pagos Registrados
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Recibo</th>
                            <th>Fecha</th>
                            <th>Cobrador</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($factura->pagos as $pago)
                        <tr>
                            <td><a href="{{ route('pagos.show', $pago) }}">{{ $pago->numero_recibo }}</a></td>
                            <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                            <td>{{ $pago->cobrador->nombre ?? 'Oficina' }}</td>
                            <td>{{ ucfirst($pago->metodo_pago) }}</td>
                            <td class="text-end text-success fw-bold">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Sin pagos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
