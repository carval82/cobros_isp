<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobro extends Model
{
    protected $fillable = [
        'cobrador_id',
        'fecha',
        'estado',
        'total_recaudado',
        'total_comision',
        'cantidad_pagos',
        'observaciones',
        'fecha_cierre',
        'liquidacion_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_cierre' => 'datetime',
        'total_recaudado' => 'decimal:2',
        'total_comision' => 'decimal:2',
    ];

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function cerrar(): void
    {
        $this->total_recaudado = $this->pagos()->sum('monto');
        $this->cantidad_pagos = $this->pagos()->count();
        $this->total_comision = $this->total_recaudado * ($this->cobrador->comision_porcentaje / 100);
        $this->estado = 'cerrado';
        $this->fecha_cierre = now();
        $this->save();
    }

    public function recalcularTotales(): void
    {
        $this->total_recaudado = $this->pagos()->sum('monto');
        $this->cantidad_pagos = $this->pagos()->count();
        $this->total_comision = $this->total_recaudado * ($this->cobrador->comision_porcentaje / 100);
        $this->save();
    }
}
