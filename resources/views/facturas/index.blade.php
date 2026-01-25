@extends('layouts.app')

@section('title', 'Facturas - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-file-invoice-dollar me-2"></i>Facturas
    </h1>
    <div class="btn-group">
        <a href="{{ route('facturas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nueva Factura
        </a>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalGenerarMes">
            <i class="fas fa-calendar-plus me-1"></i>Generar Mes
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar cliente..." value="{{ request('buscar') }}">
            </div>
            <div class="col-md-2">
                <select name="mes" class="form-select">
                    <option value="">Mes</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('mes') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <select name="anio" class="form-select">
                    <option value="">Año</option>
                    @for($i = now()->year; $i >= 2020; $i--)
                        <option value="{{ $i }}" {{ request('anio') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Estado</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pagada" {{ request('estado') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                    <option value="parcial" {{ request('estado') == 'parcial' ? 'selected' : '' }}>Parcial</option>
                    <option value="vencida" {{ request('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Periodo</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturas as $factura)
                    <tr>
                        <td><strong>{{ $factura->numero }}</strong></td>
                        <td><a href="{{ route('clientes.show', $factura->cliente_id) }}">{{ $factura->cliente->nombre }}</a></td>
                        <td>{{ $factura->periodo }}</td>
                        <td class="text-end">${{ number_format($factura->total, 0, ',', '.') }}</td>
                        <td class="text-end {{ $factura->saldo > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                            ${{ number_format($factura->saldo, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @switch($factura->estado)
                                @case('pagada')
                                    <span class="badge bg-success">Pagada</span>
                                    @break
                                @case('pendiente')
                                    <span class="badge bg-warning">Pendiente</span>
                                    @break
                                @case('parcial')
                                    <span class="badge bg-info">Parcial</span>
                                    @break
                                @case('vencida')
                                    <span class="badge bg-danger">Vencida</span>
                                    @break
                                @case('anulada')
                                    <span class="badge bg-secondary">Anulada</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('facturas.show', $factura) }}" class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($factura->saldo > 0)
                                <a href="{{ route('pagos.create', ['factura_id' => $factura->id]) }}" class="btn btn-outline-success" title="Registrar Pago">
                                    <i class="fas fa-dollar-sign"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No hay facturas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($facturas->hasPages())
    <div class="card-footer">{{ $facturas->withQueryString()->links() }}</div>
    @endif
</div>

<!-- Modal Generar Mes -->
<div class="modal fade" id="modalGenerarMes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('facturas.generar-mes-proyecto') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Generar Facturas del Mes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Se generarán facturas automáticamente para todos los servicios activos que no tengan factura en el período seleccionado.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Proyecto</label>
                            <select name="proyecto_id" class="form-select">
                                <option value="">Todos los proyectos</option>
                                @foreach(\App\Models\Proyecto::where('activo', true)->orderBy('nombre')->get() as $proyecto)
                                    <option value="{{ $proyecto->id }}">{{ $proyecto->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Mes</label>
                            <select name="mes" class="form-select" required>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>
                                        {{ $i }} - {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Año</label>
                            <select name="anio" class="form-select" required>
                                @for($i = now()->year; $i >= 2020; $i--)
                                    <option value="{{ $i }}" {{ now()->year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Generar Facturas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
