<?php

namespace App\Services;

use App\Models\Cobrador;
use App\Models\ParticipacionProyecto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UsuarioAppService
{
    public function crear(array $data): User
    {
        $role = $data['role'] ?? 'oficina';
        $password = empty($data['password']) ? str()->password(10) : $data['password'];

        $usuario = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'role' => $role,
            'documento' => ($data['documento'] ?? null) ?: null,
        ]);

        if ($role === 'cobrador') {
            $this->syncCobrador($usuario, $data, true);
        }

        if ($role === 'socio') {
            $this->syncSocio($usuario, $data, true);
        }

        return $usuario->fresh('cobrador');
    }

    public function actualizar(User $usuario, array $data): User
    {
        if ($usuario->isAdmin() && ($data['role'] ?? $usuario->role) !== 'admin') {
            $otrosAdmins = User::where('role', 'admin')->where('id', '!=', $usuario->id)->count();
            if ($otrosAdmins === 0) {
                throw ValidationException::withMessages([
                    'role' => 'Debe quedar al menos un administrador.',
                ]);
            }
        }

        $payload = [
            'name' => $data['name'] ?? $usuario->name,
            'email' => $data['email'] ?? $usuario->email,
            'role' => $data['role'] ?? $usuario->role,
            'documento' => ($data['documento'] ?? $usuario->documento) ?: null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $usuario->update($payload);

        if ($usuario->role === 'cobrador') {
            $this->syncCobrador($usuario, $data, false);
        }

        if ($usuario->role === 'socio') {
            $this->syncSocio($usuario, $data, false);
        }

        return $usuario->fresh('cobrador');
    }

    private function syncCobrador(User $usuario, array $data, bool $creating): void
    {
        $cobrador = $usuario->cobrador ?: new Cobrador(['user_id' => $usuario->id]);
        $documento = $data['documento'] ?? $cobrador->documento;
        $duplicado = Cobrador::where('documento', $documento)
            ->when($cobrador->exists, fn ($q) => $q->where('id', '!=', $cobrador->id))
            ->exists();
        if ($duplicado) {
            throw ValidationException::withMessages([
                'documento' => 'Ya existe un cobrador con ese documento.',
            ]);
        }

        $cobrador->fill([
            'nombre' => $usuario->name,
            'documento' => $documento,
            'celular' => $data['celular'] ?? $cobrador->celular,
            'email' => $usuario->email,
            'comision_porcentaje' => $data['comision_porcentaje'] ?? $cobrador->comision_porcentaje ?? 5,
            'estado' => 'activo',
            'user_id' => $usuario->id,
        ]);

        if (! empty($data['pin'])) {
            $cobrador->pin = Hash::make($data['pin']);
        } elseif ($creating) {
            throw ValidationException::withMessages([
                'pin' => 'El PIN es obligatorio para el cobrador.',
            ]);
        }

        $cobrador->save();

        if (array_key_exists('proyectos', $data)) {
            $cobrador->proyectos()->sync($data['proyectos'] ?? []);
        }
    }

    private function syncSocio(User $usuario, array $data, bool $creating): void
    {
        $documento = $data['documento'] ?? $usuario->documento;
        if (! $documento) {
            throw ValidationException::withMessages([
                'documento' => 'El documento es obligatorio para el socio.',
            ]);
        }

        if ($creating && empty($data['proyecto_id'])) {
            throw ValidationException::withMessages([
                'proyecto_id' => 'Asigna un proyecto al socio.',
            ]);
        }

        if (! empty($data['proyecto_id'])) {
            ParticipacionProyecto::updateOrCreate(
                [
                    'proyecto_id' => $data['proyecto_id'],
                    'socio_documento' => $documento,
                ],
                [
                    'socio_nombre' => $usuario->name,
                    'socio_telefono' => $data['telefono'] ?? null,
                    'porcentaje' => $data['porcentaje'] ?? 0,
                    'activo' => true,
                ]
            );
        }
    }
}
