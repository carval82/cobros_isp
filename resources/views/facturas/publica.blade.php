<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura {{ $factura->numero }} - INTERVEREDANET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3f6f4; color: #14241d; }
        .card { border: 0; border-radius: 18px; box-shadow: 0 10px 30px rgba(20,36,29,.06); }
        .brand { color: #0b6b4f; font-weight: 800; }
        .muted { color: #5b6b64; }
    </style>
</head>
<body>
<div class="container py-4" style="max-width: 720px">
    <div class="text-center mb-3">
        <div class="brand fs-3">INTERVEREDANET</div>
        <div class="muted">Factura de cobro · no necesitas iniciar sesión</div>
    </div>

    <div class="card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="muted small">Cliente</div>
                <div class="fs-5 fw-bold">{{ $factura->cliente->nombre }}</div>
                <div class="muted">{{ $factura->cliente->codigo }} · {{ $factura->cliente->documento }}</div>
            </div>
            <div class="text-end">
                <div class="muted small">Factura</div>
                <div class="fw-bold">{{ $factura->numeroMostrar() }}</div>
                <div>{{ $factura->periodo }}</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-6">
                <div class="muted small">Vence</div>
                <div>{{ optional($factura->fecha_vencimiento)->format('d/m/Y') }}</div>
            </div>
            <div class="col-6">
                <div class="muted small">Estado</div>
                <div class="fw-bold text-capitalize">{{ $factura->estado }}</div>
            </div>
            <div class="col-6">
                <div class="muted small">Total</div>
                <div class="fw-bold">${{ number_format($factura->total, 0, ',', '.') }}</div>
            </div>
            <div class="col-6">
                <div class="muted small">Saldo</div>
                <div class="fw-bold {{ $factura->saldo > 0 ? 'text-danger' : 'text-success' }}">${{ number_format($factura->saldo, 0, ',', '.') }}</div>
            </div>
        </div>

        @if($factura->concepto)
            <hr>
            <div class="muted small">Concepto</div>
            <div>{{ $factura->concepto }}</div>
        @endif
    </div>

    @if($factura->cufe || $factura->qr_code)
    <div class="card p-4 mb-3">
        @include('facturas.partials.electronica', ['compact' => true])
    </div>
    @endif

    <div class="d-grid gap-2">
        <a class="btn btn-success btn-lg" href="{{ route('facturas.publica.pdf', $token) }}">
            Ver / descargar PDF
        </a>
    </div>
</div>
</body>
</html>
