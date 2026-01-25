<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanServicio extends Model
{
    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
        'velocidad_bajada',
        'velocidad_subida',
        'precio',
        'tipo',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} ({$this->velocidad_bajada}/{$this->velocidad_subida} Mbps)";
    }
}
