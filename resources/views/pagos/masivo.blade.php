@extends('layouts.app')

@section('title', 'Pagos masivos - Cobros ISP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-check-double me-2"></i>Pagos masivos
    </h1>
    <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver a pagos
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Proyecto</label>
                <select name="proyecto_id" class="form-select" required>
                    <option value="">Seleccionar proyecto</option>
                    @foreach($proyectos as $proyecto)
                        <option value="{{ $proyecto->id }}" {{ (int) $proyectoId === (int) $proyecto->id ? 'selected' : '' }}>{{ $proyecto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mes</label>
                <select name="mes" class="form-select">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ (int) $mes === (int) $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Año</label>
                <select name="anio" class="form-select">
                    @for($i = now()->year; $i >= 2020; $i--)
                        <option value="{{ $i }}" {{ (int) $anio === $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Ver facturas
                </button>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">Marca las facturas y se cobra el saldo completo de cada una. El pago entra en la liquidación de ese mes, no en el mes que va en curso.</p>
    </div>
</div>

@if($proyectoId)
<form action="{{ route('pagos.masivo.aplicar') }}" method="POST" id="formPagosMasivos">
    @csrf
    <input type="hidden" name="proyecto_id" value="{{ $proyectoId }}">
    <input type="hidden" name="mes" value="{{ $mes }}">
    <input type="hidden" name="anio" value="{{ $anio }}">

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                @php
                    $inicioMes = \Carbon\Carbon::create((int) $anio, (int) $mes, 1);
                    $finMes = $inicioMes->copy()->endOfMonth();
                    $fechaSugerida = now()->lt($inicioMes) ? $inicioMes : (now()->gt($finMes) ? $finMes : now());
                @endphp
                <div class="col-md-3">
                    <label class="form-label">Fecha en {{ $meses[(int) $mes] ?? $mes }} {{ $anio }}</label>
                    <input type="date" name="fecha_pago" class="form-control @error('fecha_pago') is-invalid @enderror" min="{{ $inicioMes->format('Y-m-d') }}" max="{{ $finMes->format('Y-m-d') }}" value="{{ old('fecha_pago', $fechaSugerida->format('Y-m-d')) }}" required>
                    @error('fecha_pago')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Método</label>
                    <select name="metodo_pago" class="form-select" required>
                        @foreach(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'nequi' => 'Nequi', 'daviplata' => 'Daviplata', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('metodo_pago', 'efectivo') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cobrador</label>
                    <select name="cobrador_id" class="form-select">
                        <option value="">Oficina (sin cobrador)</option>
                        @foreach($cobradores as $cobrador)
                            <option value="{{ $cobrador->id }}" {{ (string) old('cobrador_id') === (string) $cobrador->id ? 'selected' : '' }}>{{ $cobrador->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referencia</label>
                    <input type="text" name="referencia_pago" class="form-control" value="{{ old('referencia_pago') }}" maxlength="100" placeholder="Opcional">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notas" class="form-control" value="{{ old('notas', 'Pago masivo') }}" maxlength="500">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>{{ $facturas->count() }}</strong> facturas con saldo
                · Marcadas: <strong id="cuentaMarcadas">0</strong>
                · Total: <strong id="totalMarcado">$0</strong>
            </div>
            <input type="search" id="buscarCliente" class="form-control form-control-sm" style="max-width: 260px" placeholder="Buscar cliente en la lista">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaMasiva">
                    <thead>
                        <tr>
                            <th style="width: 42px" class="text-center">
                                <input type="checkbox" class="form-check-input" id="marcarTodas" title="Marcar visibles">
                            </th>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Periodo</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facturas as $factura)
                        <tr data-nombre="{{ mb_strtolower($factura->cliente->nombre ?? '') }}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input pago-check" name="facturas[]" value="{{ $factura->id }}" data-saldo="{{ $factura->saldo }}">
                            </td>
                            <td>{{ $factura->numeroMostrar() }}</td>
                            <td>{{ $factura->cliente->nombre }}</td>
                            <td>{{ $factura->periodo }}</td>
                            <td class="text-end text-danger fw-bold">${{ number_format($factura->saldo, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($factura->estado === 'parcial')
                                    <span class="badge bg-info">Parcial</span>
                                @elseif($factura->estado === 'vencida')
                                    <span class="badge bg-danger">Vencida</span>
                                @else
                                    <span class="badge bg-warning">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Este proyecto no tiene facturas con saldo en el mes elegido.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($facturas->isNotEmpty())
        <div class="card-footer d-flex justify-content-between align-items-center">
            @error('facturas')<div class="text-danger small">{{ $message }}</div>@else<span class="text-muted small">El pago usa el saldo de cada casilla marcada.</span>@enderror
            <button type="submit" class="btn btn-success" id="btnAplicar" disabled>
                <i class="fas fa-dollar-sign me-1"></i>Aplicar pagos
            </button>
        </div>
        @endif
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('formPagosMasivos');
    if (!form) return;

    const marcarTodas = document.getElementById('marcarTodas');
    const buscar = document.getElementById('buscarCliente');
    const cuenta = document.getElementById('cuentaMarcadas');
    const total = document.getElementById('totalMarcado');
    const boton = document.getElementById('btnAplicar');
    const formato = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

    function visibles() {
        return [...document.querySelectorAll('#tablaMasiva tbody tr')].filter((fila) => fila.style.display !== 'none' && fila.querySelector('.pago-check'));
    }

    function actualizar() {
        const marcadas = [...document.querySelectorAll('.pago-check:checked')];
        const suma = marcadas.reduce((acum, casilla) => acum + Number(casilla.dataset.saldo || 0), 0);
        cuenta.textContent = String(marcadas.length);
        total.textContent = formato.format(suma);
        boton.disabled = marcadas.length === 0;
        const filas = visibles();
        marcarTodas.checked = filas.length > 0 && filas.every((fila) => fila.querySelector('.pago-check').checked);
    }

    document.querySelectorAll('.pago-check').forEach((casilla) => casilla.addEventListener('change', actualizar));

    marcarTodas.addEventListener('change', function () {
        visibles().forEach((fila) => {
            fila.querySelector('.pago-check').checked = marcarTodas.checked;
        });
        actualizar();
    });

    buscar.addEventListener('input', function () {
        const texto = buscar.value.trim().toLowerCase();
        document.querySelectorAll('#tablaMasiva tbody tr[data-nombre]').forEach((fila) => {
            fila.style.display = fila.dataset.nombre.includes(texto) ? '' : 'none';
        });
        actualizar();
    });

    form.addEventListener('submit', function (evento) {
        const marcadas = document.querySelectorAll('.pago-check:checked').length;
        if (!marcadas) {
            evento.preventDefault();
            return;
        }
        if (!confirm('Se van a registrar ' + marcadas + ' pagos por el saldo de cada factura. ¿Continuar?')) {
            evento.preventDefault();
        }
    });

    actualizar();
})();
</script>
@endpush
