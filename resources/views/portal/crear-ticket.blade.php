@extends('portal.layout')

@section('title', 'Nuevo Reporte')

@section('content')
<div class="mb-4">
    <h2 class="text-white mb-1">Nuevo Reporte</h2>
    <p class="text-muted mb-0">Cuéntanos cómo podemos ayudarte</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-portal">
            <div class="card-body">
                <form action="{{ route('portal.tickets.guardar') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label text-white">Tipo de Reporte *</label>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <input type="radio" class="btn-check" name="tipo" id="tipo-dano" value="daño" {{ old('tipo') == 'daño' ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger w-100 py-3" for="tipo-dano">
                                    <i class="bi bi-exclamation-triangle d-block mb-1" style="font-size: 1.5rem;"></i>
                                    Daño
                                </label>
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="radio" class="btn-check" name="tipo" id="tipo-cobro" value="cobro" {{ old('tipo') == 'cobro' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary w-100 py-3" for="tipo-cobro">
                                    <i class="bi bi-cash-coin d-block mb-1" style="font-size: 1.5rem;"></i>
                                    Cobro
                                </label>
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="radio" class="btn-check" name="tipo" id="tipo-soporte" value="soporte" {{ old('tipo', 'soporte') == 'soporte' ? 'checked' : '' }}>
                                <label class="btn btn-outline-info w-100 py-3" for="tipo-soporte">
                                    <i class="bi bi-headset d-block mb-1" style="font-size: 1.5rem;"></i>
                                    Soporte
                                </label>
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="radio" class="btn-check" name="tipo" id="tipo-otro" value="otro" {{ old('tipo') == 'otro' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary w-100 py-3" for="tipo-otro">
                                    <i class="bi bi-three-dots d-block mb-1" style="font-size: 1.5rem;"></i>
                                    Otro
                                </label>
                            </div>
                        </div>
                        @error('tipo')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white">Asunto *</label>
                        <input type="text" name="asunto" class="form-control bg-dark text-white border-secondary" 
                               placeholder="Ej: Sin servicio de internet" value="{{ old('asunto') }}" required>
                        @error('asunto')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white">Descripción *</label>
                        <textarea name="descripcion" class="form-control bg-dark text-white border-secondary" 
                                  rows="5" placeholder="Describe tu problema o solicitud con el mayor detalle posible..." required>{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-portal">
                            <i class="bi bi-send me-2"></i>Enviar Reporte
                        </button>
                        <a href="{{ route('portal.tickets') }}" class="btn btn-outline-light">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-portal">
            <div class="card-header">
                <i class="bi bi-lightbulb me-2"></i>Tipos de Reporte
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Daño</h6>
                    <small class="text-muted">Reporta problemas técnicos como caídas de servicio, lentitud o fallas en el equipo.</small>
                </div>
                <div class="mb-3">
                    <h6 class="text-primary"><i class="bi bi-cash-coin me-2"></i>Cobro</h6>
                    <small class="text-muted">Solicita que un cobrador pase por tu domicilio o consultas sobre facturación.</small>
                </div>
                <div class="mb-3">
                    <h6 class="text-info"><i class="bi bi-headset me-2"></i>Soporte</h6>
                    <small class="text-muted">Ayuda general con tu servicio, cambio de plan, o configuración de equipos.</small>
                </div>
                <div>
                    <h6 class="text-secondary"><i class="bi bi-three-dots me-2"></i>Otro</h6>
                    <small class="text-muted">Cualquier otra consulta o sugerencia que quieras hacernos.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
