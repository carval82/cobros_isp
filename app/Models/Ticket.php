<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'cliente_id',
        'proyecto_id',
        'tipo',
        'asunto',
        'descripcion',
        'estado',
        'prioridad',
        'respuesta',
        'atendido_por',
        'fecha_respuesta',
    ];

    protected $casts = [
        'fecha_respuesta' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'abierto' => 'warning',
            'en_proceso' => 'info',
            'resuelto' => 'success',
            'cerrado' => 'secondary',
            default => 'secondary',
        };
    }

    public function getTipoBadgeAttribute(): string
    {
        return match($this->tipo) {
            'daño' => 'danger',
            'cobro' => 'primary',
            'soporte' => 'info',
            'otro' => 'secondary',
            default => 'secondary',
        };
    }
}
