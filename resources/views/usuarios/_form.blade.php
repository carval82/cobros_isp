@php
    $isEdit = isset($usuario);
    $role = old('role', $isEdit ? $usuario->role : 'oficina');
    $selectedProyectos = old('proyectos', $isEdit ? ($usuario->cobrador?->proyectos->pluck('id')->all() ?? []) : []);
    $participacion = $participacion ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre *</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario?->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Correo *</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario?->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Rol de la aplicación *</label>
        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach($roles as $id => $label)
                <option value="{{ $id }}" @selected($role === $id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Admin: todo el panel. Oficina: opera sin usuarios. Cobrador y socio entran por la app con documento y PIN.</small>
    </div>
</div>

<div id="fields-password" class="row g-3 mt-1 {{ in_array($role, ['admin', 'oficina'], true) ? '' : 'd-none' }}">
    <div class="col-md-6">
        <label class="form-label">{{ $isEdit ? 'Nueva contraseña' : 'Contraseña *' }}</label>
        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" minlength="6" placeholder="{{ $isEdit ? 'Dejar vacío para no cambiar' : '' }}">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Mínimo 6 caracteres. Se usa para entrar a la web y a la app admin.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ $isEdit ? 'Confirmar nueva contraseña' : 'Confirmar contraseña *' }}</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="6">
    </div>
</div>

<div id="fields-cobrador" class="row g-3 mt-1 {{ $role === 'cobrador' ? '' : 'd-none' }}">
    <div class="col-md-4">
        <label class="form-label">Documento (cédula) *</label>
        <input type="text" name="documento" id="documento_cobrador" class="form-control @error('documento') is-invalid @enderror" value="{{ old('documento', $usuario?->documento ?? $usuario?->cobrador?->documento ?? '') }}">
        @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Login de cobrador en la app</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">PIN app {{ $isEdit ? '' : '*' }}</label>
        <input type="password" name="pin" id="pin" class="form-control @error('pin') is-invalid @enderror" minlength="4" maxlength="6" placeholder="{{ $isEdit ? 'Dejar vacío para no cambiar' : '4-6 dígitos' }}">
        @error('pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Celular</label>
        <input type="text" name="celular" class="form-control" value="{{ old('celular', $usuario?->cobrador?->celular ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Comisión (%)</label>
        <input type="number" name="comision_porcentaje" class="form-control" value="{{ old('comision_porcentaje', $usuario?->cobrador?->comision_porcentaje ?? 5) }}" min="0" max="100" step="0.5">
    </div>
    <div class="col-md-8">
        <label class="form-label">Proyectos asignados</label>
        <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
            @forelse($proyectos as $proyecto)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="proyectos[]" value="{{ $proyecto->id }}" id="proyecto_{{ $proyecto->id }}"
                        {{ in_array($proyecto->id, $selectedProyectos) ? 'checked' : '' }}>
                    <label class="form-check-label" for="proyecto_{{ $proyecto->id }}">{{ $proyecto->nombre }}</label>
                </div>
            @empty
                <div class="text-muted small">No hay proyectos activos</div>
            @endforelse
        </div>
    </div>
</div>

<div id="fields-socio" class="row g-3 mt-1 {{ $role === 'socio' ? '' : 'd-none' }}">
    <div class="col-md-4">
        <label class="form-label">Documento *</label>
        <input type="text" name="documento" id="documento_socio" class="form-control @error('documento') is-invalid @enderror" value="{{ old('documento', $usuario?->documento ?? $participacion?->socio_documento ?? '') }}">
        @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">El PIN de la app son los últimos 4 dígitos del documento</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $participacion?->socio_telefono ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Proyecto *</label>
        <select name="proyecto_id" id="proyecto_id" class="form-select @error('proyecto_id') is-invalid @enderror">
            <option value="">Seleccione</option>
            @foreach($proyectos as $proyecto)
                <option value="{{ $proyecto->id }}" @selected((string) old('proyecto_id', $participacion?->proyecto_id) === (string) $proyecto->id)>{{ $proyecto->nombre }}</option>
            @endforeach
        </select>
        @error('proyecto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Participación (%)</label>
        <input type="number" name="porcentaje" class="form-control" value="{{ old('porcentaje', $participacion?->porcentaje ?? 0) }}" min="0" max="100" step="0.5">
    </div>
</div>

@push('scripts')
<script>
function setSection(box, enabled) {
    box.classList.toggle('d-none', !enabled);
    box.querySelectorAll('input, select, textarea').forEach(function (el) {
        el.disabled = !enabled;
    });
}

function toggleRoleFields() {
    const role = document.getElementById('role').value;
    const isEdit = {{ $isEdit ? 'true' : 'false' }};
    const panel = role === 'admin' || role === 'oficina';

    setSection(document.getElementById('fields-password'), panel);
    setSection(document.getElementById('fields-cobrador'), role === 'cobrador');
    setSection(document.getElementById('fields-socio'), role === 'socio');

    document.getElementById('password').required = panel && !isEdit;
    document.getElementById('password_confirmation').required = panel && !isEdit;
    document.getElementById('documento_cobrador').required = role === 'cobrador';
    document.getElementById('pin').required = role === 'cobrador' && !isEdit;
    document.getElementById('documento_socio').required = role === 'socio';
    document.getElementById('proyecto_id').required = role === 'socio' && !isEdit;
}

document.getElementById('role').addEventListener('change', toggleRoleFields);
toggleRoleFields();
</script>
@endpush
