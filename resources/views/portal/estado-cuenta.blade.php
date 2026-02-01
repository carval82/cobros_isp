@extends('portal.layout')

@section('title', 'Estado de Cuenta')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white mb-1">Estado de Cuenta</h2>
        <p class="text-muted mb-0">Historial de facturas y pagos</p>
    </div>
    <div class="text-end">
        <p class="text-muted mb-0 small">Código Cliente</p>
        <h5 class="text-white mb-0">{{ $cliente->codigo }}</h5>
    </div>
</div>

<div class="card card-portal">
    <div class="card-body">
        @if($facturas->count() > 0)
            <div class="table-responsive">
                <table class="table table-portal">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Período</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Pagado</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facturas as $factura)
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $factura->numero }}</span>
                            </td>
                            <td>{{ $factura->periodo }}</td>
                            <td>{{ $factura->fecha_emision ? $factura->fecha_emision->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if($factura->fecha_vencimiento < now() && $factura->estado != 'pagada')
                                    <span class="text-danger">{{ $factura->fecha_vencimiento->format('d/m/Y') }}</span>
                                @else
                                    {{ $factura->fecha_vencimiento->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="text-end">${{ number_format($factura->total, 0, ',', '.') }}</td>
                            <td class="text-end text-success">
                                ${{ number_format($factura->total - $factura->saldo, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold {{ $factura->saldo > 0 ? 'text-warning' : 'text-success' }}">
                                ${{ number_format($factura->saldo, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @switch($factura->estado)
                                    @case('pagada')
                                        <span class="badge bg-success">Pagada</span>
                                        @break
                                    @case('vencida')
                                        <span class="badge bg-danger">Vencida</span>
                                        @break
                                    @case('parcial')
                                        <span class="badge bg-warning">Parcial</span>
                                        @break
                                    @default
                                        <span class="badge bg-info">Pendiente</span>
                                @endswitch
                            </td>
                        </tr>
                        @if($factura->pagos->count() > 0)
                        <tr class="bg-dark bg-opacity-25">
                            <td colspan="8" class="py-2 ps-5">
                                <small class="text-muted">
                                    <i class="bi bi-arrow-return-right me-2"></i>
                                    <strong>Pagos:</strong>
                                    @foreach($factura->pagos as $pago)
                                        <span class="ms-3">
                                            {{ $pago->fecha_pago->format('d/m/Y') }} - 
                                            ${{ number_format($pago->monto, 0, ',', '.') }}
                                            ({{ ucfirst($pago->metodo_pago) }})
                                        </span>
                                    @endforeach
                                </small>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $facturas->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 mb-0">No hay facturas registradas</p>
            </div>
        @endif
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Información de Pago
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Puede realizar su pago a través de:</strong></p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-cash text-success me-2"></i> Efectivo con nuestros cobradores autorizados</li>
                    <li class="mb-2"><i class="bi bi-bank text-primary me-2"></i> Transferencia bancaria</li>
                    <li class="mb-2"><i class="bi bi-phone text-info me-2"></i> Nequi / Daviplata</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-question-circle me-2"></i>¿Necesitas ayuda?
            </div>
            <div class="card-body">
                <p class="mb-3">Si tienes alguna duda sobre tu facturación o deseas que un cobrador pase por tu domicilio:</p>
                <a href="{{ route('portal.tickets.crear') }}" class="btn btn-portal">
                    <i class="bi bi-chat-dots me-2"></i>Crear un Reporte
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
