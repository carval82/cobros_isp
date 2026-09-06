@php
    $doc = old('tipo_documento', $cliente->tipo_documento ?? 'CC');
    $fe = old('factura_electronica', $cliente->factura_electronica ?? false);
@endphp
<div class="col-12">
    <div class="border rounded p-3 bg-light">
        <div class="fw-semibold mb-2"><i class="fas fa-id-card me-1"></i>Datos para factura electrónica (DIAN / Alegra)</div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tipo documento</label>
                <select name="tipo_documento" id="tipoDocumento" class="form-select">
                    @foreach(\App\Models\Cliente::tiposDocumento() as $codigo => $etiqueta)
                        <option value="{{ $codigo }}" {{ $doc == $codigo ? 'selected' : '' }}>{{ $codigo }} — {{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cédula / NIT / RUT</label>
                <input type="text" name="documento" id="documentoCliente" class="form-control" value="{{ old('documento', $cliente->documento ?? '') }}" placeholder="Ej: 900123456-1 o la cédula">
                <small class="text-muted">Si pegas el NIT con guion (900123456-1), se separa el DV solo.</small>
            </div>
            <div class="col-md-2">
                <label class="form-label">DV</label>
                <input type="text" name="dv" id="dvCliente" class="form-control" maxlength="1" value="{{ old('dv', $cliente->dv ?? '') }}" placeholder="0-9">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo de persona</label>
                <select name="tipo_persona" class="form-select">
                    <option value="natural" {{ old('tipo_persona', $cliente->tipo_persona ?? 'natural') == 'natural' ? 'selected' : '' }}>Persona natural</option>
                    <option value="juridica" {{ old('tipo_persona', $cliente->tipo_persona ?? '') == 'juridica' ? 'selected' : '' }}>Persona jurídica</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Régimen</label>
                <select name="regimen" class="form-select">
                    <option value="simplificado" {{ old('regimen', $cliente->regimen ?? 'simplificado') == 'simplificado' ? 'selected' : '' }}>Simplificado</option>
                    <option value="comun" {{ old('regimen', $cliente->regimen ?? '') == 'comun' ? 'selected' : '' }}>Común</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Correo (para enviar la FE)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $cliente->email ?? '') }}">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="factura_electronica" value="1" class="form-check-input" id="factura_electronica" {{ $fe ? 'checked' : '' }}>
                    <label class="form-check-label" for="factura_electronica">Causar factura electrónica en Alegra (requiere documento y correo)</label>
                </div>
            </div>
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
    const doc = document.getElementById('documentoCliente');
    const dv = document.getElementById('dvCliente');
    const tipo = document.getElementById('tipoDocumento');
    if (doc && dv) {
        doc.addEventListener('blur', function () {
            const m = this.value.trim().match(/^(\d+)\s*-\s*(\d)$/);
            if (m) {
                this.value = m[1];
                dv.value = m[2];
                if (tipo && tipo.value === 'CC' && m[1].length >= 9) tipo.value = 'NIT';
            }
        });
    }
</script>
@endpush
@endonce
