<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Proyecto;
use App\Services\UsuarioAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function perfil()
    {
        $usuario = Auth::user();

        return view('perfil.edit', compact('usuario'));
    }

    public function actualizarPerfil(Request $request)
    {
        $usuario = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
        ], [
            'email.unique' => 'Este correo ya está en uso',
        ]);

        $usuario->update($validated);

        return back()->with('success', 'Perfil actualizado correctamente');
    }

    public function cambiarPassword(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'password_actual' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual es incorrecta']);
        }

        $usuario->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente');
    }

    public function index()
    {
        $usuarios = User::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        $roles = User::rolesApp();
        $usuario = null;
        $participacion = null;

        return view('usuarios.create', compact('proyectos', 'roles', 'usuario', 'participacion'));
    }

    public function store(Request $request, UsuarioAppService $service)
    {
        $validated = $this->validated($request, true);
        $service->crear($validated);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $usuario)
    {
        $usuario->load('cobrador.proyectos');
        $proyectos = Proyecto::where('activo', true)->orderBy('nombre')->get();
        $roles = User::rolesApp();
        $participacion = $usuario->documento
            ? \App\Models\ParticipacionProyecto::where('socio_documento', $usuario->documento)->first()
            : null;

        return view('usuarios.edit', compact('usuario', 'proyectos', 'roles', 'participacion'));
    }

    public function update(Request $request, User $usuario, UsuarioAppService $service)
    {
        $validated = $this->validated($request, false, $usuario);
        $service->actualizar($usuario, $validated);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No puede eliminar su propio usuario');
        }

        if ($usuario->isAdmin() && User::where('role', 'admin')->where('id', '!=', $usuario->id)->count() === 0) {
            return back()->with('error', 'Debe quedar al menos un administrador');
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente');
    }

    private function validated(Request $request, bool $creating, ?User $usuario = null): array
    {
        $usuario?->loadMissing('cobrador');
        $role = $request->input('role', $usuario?->role ?? 'oficina');
        $passwordRule = $creating && in_array($role, ['admin', 'oficina'], true)
            ? 'required|string|min:6|confirmed'
            : 'nullable|string|min:6|confirmed';

        $documentoRules = [
            in_array($role, ['cobrador', 'socio'], true) ? 'required' : 'nullable',
            'string',
            'max:20',
            Rule::unique('users', 'documento')->ignore($usuario?->id),
        ];
        if ($role === 'cobrador') {
            $documentoRules[] = Rule::unique('cobradors', 'documento')->ignore($usuario?->cobrador?->id)->whereNull('deleted_at');
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'role' => 'required|in:' . implode(',', array_keys(User::rolesApp())),
            'password' => $passwordRule,
            'documento' => $documentoRules,
            'pin' => $role === 'cobrador' && $creating ? 'required|string|min:4|max:6' : 'nullable|string|min:4|max:6',
            'celular' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'comision_porcentaje' => 'nullable|numeric|min:0|max:100',
            'proyectos' => 'nullable|array',
            'proyectos.*' => 'exists:proyectos,id',
            'proyecto_id' => $role === 'socio' && $creating ? 'required|exists:proyectos,id' : 'nullable|exists:proyectos,id',
            'porcentaje' => 'nullable|numeric|min:0|max:100',
        ], [
            'email.unique' => 'Este correo ya está en uso',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'documento.required' => 'El documento es obligatorio para este rol',
            'documento.unique' => 'Este documento ya está en uso',
            'pin.required' => 'El PIN es obligatorio para el cobrador',
            'proyecto_id.required' => 'Asigna un proyecto al socio',
        ]);
    }
}
