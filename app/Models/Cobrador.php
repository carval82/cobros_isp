<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobrador extends Model
{
    use SoftDeletes;

    protected $table = 'cobradors';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'documento',
        'telefono',
        'celular',
        'email',
        'comision_porcentaje',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'comision_porcentaje' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function cobrosAbiertos()
    {
        return $this->cobros()->where('estado', 'abierto');
    }

    public function totalRecaudadoMes(int $mes = null, int $anio = null): float
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;
        
        return $this->cobros()
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->sum('total_recaudado');
    }
}
