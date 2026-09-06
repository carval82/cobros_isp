@if($factura->cufe || $factura->qr_code || $factura->alegra_id)
<div class="{{ $compact ?? false ? '' : 'card mb-3' }}">
    @unless($compact ?? false)
    <div class="card-header">
        <i class="fas fa-qrcode me-2"></i>Factura electrónica DIAN
    </div>
    @endunless
    <div class="{{ ($compact ?? false) ? '' : 'card-body' }}">
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if($factura->urlImagenQr())
            <img src="{{ $factura->urlImagenQr() }}" alt="QR DIAN" width="160" height="160" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:6px">
            @endif
            <div class="flex-grow-1">
                <div class="fw-bold mb-1">Factura Electrónica de Venta</div>
                <div class="small text-muted mb-2">{{ $factura->numeroMostrar() }}
                    @if($factura->estado_dian)
                        · DIAN: {{ $factura->estado_dian }}
                    @endif
                </div>
                @if($factura->cufe)
                <div class="small"><strong>CUFE</strong></div>
                <div class="small text-break" style="font-family:monospace;word-break:break-all">{{ $factura->cufe }}</div>
                @else
                <div class="small text-warning">Aún no llega el CUFE. Vuelve a pulsar “Causar en Alegra” en unos segundos.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
