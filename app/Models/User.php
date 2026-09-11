<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $attributes = [
        'role' => 'admin',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'documento',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function rolesApp(): array
    {
        return [
            'admin' => 'Administrador',
            'oficina' => 'Oficina',
            'cobrador' => 'Cobrador',
            'socio' => 'Socio',
        ];
    }

    public function etiquetaRol(): string
    {
        $role = $this->role ?: 'admin';

        return self::rolesApp()[$role] ?? $role;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?: 'admin') === 'admin';
    }

    public function canUseAdminPanel(): bool
    {
        return in_array($this->role ?: 'admin', ['admin', 'oficina'], true);
    }

    public function cobrador(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cobrador::class);
    }
}
