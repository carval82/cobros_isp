@extends('layouts.app')

@section('title', 'Mi perfil - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-circle me-2"></i>Mi perfil
    </h1>
    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-users-cog me-1"></i>Gestionar usuarios
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Datos de la cuenta</div>
            <div class="card-body">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Cambiar contraseña</div>
            <div class="card-body">
                <form action="{{ route('perfil.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Contraseña actual *</label>
                        <input type="password" name="password_actual" class="form-control @error('password_actual') is-invalid @enderror" required>
                        @error('password_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña *</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="6" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar nueva contraseña *</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-key me-1"></i>Actualizar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
