@extends('layouts.app')

@section('title', 'Pagos - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-money-bill-wave me-2"></i>Pagos
    </h1>
    <a href="{{ route('pagos.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Pago
    </a>
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
@endsection
