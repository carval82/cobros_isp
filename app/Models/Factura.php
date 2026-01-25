<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero',
        'cliente_id',
        'servicio_id',
        'mes',
        'anio',
        'fecha_emision',
        'fecha_vencimiento',
        'subtotal',
        'descuento',
        'recargo',
        'total',
        'saldo',
        'estado',
        'concepto',
        'notas',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'recargo' => 'decimal:2',
        'total' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function getPeriodoAttribute(): string
    {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return $meses[$this->mes] . ' ' . $this->anio;
    }

    public function estaVencida(): bool
    {
        return $this->estado === 'pendiente' && $this->fecha_vencimiento < now();
    }

    public function registrarPago(float $monto): void
    {
        $this->saldo -= $monto;
        
        if ($this->saldo <= 0) {
            $this->saldo = 0;
            $this->estado = 'pagada';
        } else {
            $this->estado = 'parcial';
        }
        
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($factura) {
            if (empty($factura->numero)) {
                $ultimo = static::withTrashed()->max('id') ?? 0;
                $factura->numero = 'FAC-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
            }
            if (empty($factura->saldo)) {
                $factura->saldo = $factura->total;
            }
        });
    }
}
