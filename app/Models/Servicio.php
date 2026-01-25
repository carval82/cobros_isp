<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'plan_servicio_id',
        'ip_asignada',
        'mac_address',
        'equipo_modelo',
        'equipo_serial',
        'dia_corte',
        'dia_pago_limite',
        'fecha_inicio',
        'fecha_fin',
        'precio_especial',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'precio_especial' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function planServicio(): BelongsTo
    {
        return $this->belongsTo(PlanServicio::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function getPrecioMensualAttribute(): float
    {
        return $this->precio_especial ?? $this->planServicio->precio ?? 0;
    }

    public function tieneFacturaMes(int $mes, int $anio): bool
    {
        return $this->facturas()->where('mes', $mes)->where('anio', $anio)->exists();
    }
}
