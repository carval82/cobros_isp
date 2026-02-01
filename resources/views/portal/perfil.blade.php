@extends('portal.layout')

@section('title', 'Mi Perfil')

@section('content')
<div class="mb-4">
    <h2 class="text-white mb-1">Mi Perfil</h2>
    <p class="text-muted mb-0">Administra tu información personal</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-portal mb-4">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Información Personal
            </div>
            <div class="card-body">
                <form action="{{ route('portal.perfil.actualizar') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Nombre</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" 
                                   value="{{ $cliente->nombre }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Documento</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" 
                                   value="{{ $cliente->documento }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white">Celular</label>
                            <input type="text" name="celular" class="form-control bg-dark text-white border-secondary" 
                                   value="{{ old('celular', $cliente->celular) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Email</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary" 
                                   value="{{ old('email', $cliente->email) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Dirección</label>
                        <input type="text" name="direccion" class="form-control bg-dark text-white border-secondary" 
                               value="{{ old('direccion', $cliente->direccion) }}">
                    </div>

                    <button type="submit" class="btn btn-portal">
                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-key me-2"></i>Cambiar PIN
            </div>
            <div class="card-body">
                <form action="{{ route('portal.perfil.pin') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-white">PIN Actual</label>
                            <input type="password" name="pin_actual" class="form-control bg-dark text-white border-secondary" 
                                   maxlength="4" placeholder="****" required>
                            @error('pin_actual')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white">Nuevo PIN</label>
                            <input type="password" name="pin_nuevo" class="form-control bg-dark text-white border-secondary" 
                                   maxlength="4" placeholder="****" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white">Confirmar PIN</label>
                            <input type="password" name="pin_nuevo_confirmation" class="form-control bg-dark text-white border-secondary" 
                                   maxlength="4" placeholder="****" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-shield-lock me-2"></i>Cambiar PIN
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-credit-card me-2"></i>Mi Cuenta
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; font-size: 2rem; color: #fff;">
                        {{ substr($cliente->nombre, 0, 1) }}
                    </div>
                </div>

                <div class="mb-3 pb-3 border-bottom border-secondary">
                    <small class="text-muted d-block">Código de Cliente</small>
                    <span class="text-white fw-bold">{{ $cliente->codigo }}</span>
                </div>

                <div class="mb-3 pb-3 border-bottom border-secondary">
                    <small class="text-muted d-block">Proyecto</small>
                    <span class="text-white">{{ $cliente->proyecto->nombre ?? 'N/A' }}</span>
                </div>

                <div class="mb-3 pb-3 border-bottom border-secondary">
                    <small class="text-muted d-block">Estado</small>
                    @if($cliente->estado == 'activo')
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-danger">{{ ucfirst($cliente->estado) }}</span>
                    @endif
                </div>

                @php $servicio = $cliente->servicioActivo(); @endphp
                @if($servicio)
                <div>
                    <small class="text-muted d-block">Plan Actual</small>
                    <span class="text-white">{{ $servicio->planServicio->nombre ?? 'N/A' }}</span>
                    <br>
                    <small class="text-info">
                        ${{ number_format($servicio->precio_mensual, 0, ',', '.') }}/mes
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
