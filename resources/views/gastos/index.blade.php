@extends('layouts.app')

@section('title', 'Gastos de proyecto - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-receipt me-2"></i>Gastos por proyecto</h1>
    <div class="btn-group">
        <a href="{{ route('liquidaciones.socios', ['mes' => $mes, 'anio' => $anio]) }}" class="btn btn-outline-secondary">Informe socios</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGasto">
            <i class="fas fa-plus me-1"></i>Registrar gasto
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Proyecto</label>
                <select name="proyecto_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($proyectos as $proyecto)
                        <option value="{{ $proyecto->id }}" {{ $proyectoId == $proyecto->id ? 'selected' : '' }}>{{ $proyecto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mes</label>
                <select name="mes" class="form-select">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Año</label>
                <input type="number" name="anio" class="form-control" value="{{ $anio }}" min="2024" max="2035">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="{{ route('gastos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Gastos del período</span>
        <strong>Total: ${{ number_format($total, 0, ',', '.') }}</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Proyecto</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Proveedor</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                        <td>{{ $gasto->proyecto->nombre ?? '—' }}</td>
                        <td>{{ $categorias[$gasto->categoria] ?? $gasto->categoria }}</td>
                        <td>{{ $gasto->descripcion }}</td>
                        <td>{{ $gasto->proveedor ?: '—' }}</td>
                        <td class="text-end fw-bold">${{ number_format($gasto->monto, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <form action="{{ route('gastos.destroy', $gasto) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este gasto?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay gastos en este período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($gastos->hasPages())
    <div class="card-footer">{{ $gastos->links() }}</div>
    @endif
</div>

<div class="modal fade" id="modalGasto" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gastos.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Registrar gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Proyecto *</label>
                    <select name="proyecto_id" class="form-select" required>
                        <option value="">Seleccione</option>
                        @foreach($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}" {{ $proyectoId == $proyecto->id ? 'selected' : '' }}>{{ $proyecto->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Categoría *</label>
                    <select name="categoria" class="form-select" required>
                        @foreach($categorias as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción *</label>
                    <input type="text" name="descripcion" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Monto *</label>
                    <input type="number" name="monto" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Proveedor</label>
                    <input type="text" name="proveedor" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">N° factura</label>
                    <input type="text" name="factura_numero" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar gasto</button>
            </div>
        </form>
    </div>
</div>
@endsection
