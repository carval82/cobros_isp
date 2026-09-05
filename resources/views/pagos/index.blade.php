@extends('layouts.app')

@section('title', 'Pagos - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-money-bill-wave me-2"></i>Pagos
    </h1>
    <div class="btn-group">
        <a href="{{ route('pagos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nuevo Pago
        </a>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalLimpiarPagos">
            <i class="fas fa-trash me-1"></i>Borrar pagos viejos
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="date" name="fecha" class="form-control" value="{{ request('fecha') }}" placeholder="Fecha">
            </div>
            <div class="col-md-3">
                <select name="cobrador_id" class="form-select">
                    <option value="">Todos los cobradores</option>
                    @foreach($cobradores as $cobrador)
                        <option value="{{ $cobrador->id }}" {{ request('cobrador_id') == $cobrador->id ? 'selected' : '' }}>
                            {{ $cobrador->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Recibo</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Factura</th>
                        <th>Cobrador</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Método</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $pago)
                    <tr>
                        <td><a href="{{ route('pagos.show', $pago) }}"><strong>{{ $pago->numero_recibo }}</strong></a></td>
                        <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                        <td>{{ $pago->factura->cliente->nombre ?? '-' }}</td>
                        <td><a href="{{ route('facturas.show', $pago->factura_id) }}">{{ $pago->factura->numero ?? '-' }}</a></td>
                        <td>{{ $pago->cobrador->nombre ?? 'Oficina' }}</td>
                        <td class="text-end text-success fw-bold">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                        <td class="text-center"><span class="badge bg-secondary">{{ ucfirst($pago->metodo_pago) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay pagos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pagos->hasPages())<div class="card-footer">{{ $pagos->withQueryString()->links() }}</div>@endif
</div>

<div class="modal fade" id="modalLimpiarPagos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('pagos.limpiar-historial') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Empezar el mes en cero</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Esto borra el historial de cartera anterior a septiembre:</p>
                    <ul class="mb-0">
                        <li>Elimina todos los pagos y recibos viejos.</li>
                        <li>Elimina cobros y liquidaciones anteriores.</li>
                        <li>Las facturas de septiembre se conservan.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check me-1"></i>Borrar pagos viejos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
