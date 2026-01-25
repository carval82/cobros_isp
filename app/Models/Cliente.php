<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'proyecto_id',
        'codigo',
        'nombre',
        'documento',
        'tipo_documento',
        'telefono',
        'celular',
        'email',
        'direccion',
        'barrio',
        'municipio',
        'departamento',
        'latitud',
        'longitud',
        'referencia_ubicacion',
        'estado',
        'fecha_instalacion',
        'notas',
        'cobrador_id',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function servicioActivo()
    {
        return $this->servicios()->where('estado', 'activo')->first();
    }

    public function saldoPendiente(): float
    {
        return $this->facturas()->whereIn('estado', ['pendiente', 'parcial', 'vencida'])->sum('saldo');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($cliente) {
            if (empty($cliente->codigo)) {
                $ultimo = static::withTrashed()->max('id') ?? 0;
                $cliente->codigo = 'CLI-' . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
