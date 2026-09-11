@extends('layouts.app')

@section('title', 'Usuarios - INTERVEREDANET')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-users-cog me-2"></i>Usuarios
    </h1>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo usuario
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Creado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td>
                            <strong>{{ $usuario->name }}</strong>
                            @if($usuario->id === auth()->id())
                                <span class="badge bg-info ms-1">Tú</span>
                            @endif
                        </td>
                        <td>{{ $usuario->email }}</td>
                        <td>
                            @php
                                $rolColor = match($usuario->role) {
                                    'admin' => 'danger',
                                    'oficina' => 'info',
                                    'cobrador' => 'success',
                                    'socio' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $rolColor }}">{{ $usuario->etiquetaRol() }}</span>
                            @if($usuario->documento)
                                <div class="small text-muted">{{ $usuario->documento }}</div>
                            @endif
                        </td>
                        <td>{{ $usuario->created_at?->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($usuario->id !== auth()->id())
                                <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar el usuario {{ $usuario->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="fas fa-users-cog fa-2x mb-2 d-block"></i>
                            No hay usuarios registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
