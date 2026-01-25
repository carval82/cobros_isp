<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liquidacion extends Model
{
    protected $table = 'liquidacions';

    protected $fillable = [
        'numero',
        'cobrador_id',
        'fecha_desde',
        'fecha_hasta',
        'fecha_liquidacion',
        'total_recaudado',
        'total_comision',
        'total_a_entregar',
        'cantidad_cobros',
        'cantidad_pagos',
        'estado',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'fecha_liquidacion' => 'date',
        'total_recaudado' => 'decimal:2',
        'total_comision' => 'decimal:2',
        'total_a_entregar' => 'decimal:2',
    ];

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($liquidacion) {
            if (empty($liquidacion->numero)) {
                $ultimo = static::max('id') ?? 0;
                $liquidacion->numero = 'LIQ-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
