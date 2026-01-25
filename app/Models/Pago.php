<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero_recibo',
        'factura_id',
        'cobrador_id',
        'cobro_id',
        'fecha_pago',
        'monto',
        'metodo_pago',
        'referencia_pago',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(Cobrador::class);
    }

    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($pago) {
            if (empty($pago->numero_recibo)) {
                $ultimo = static::withTrashed()->max('id') ?? 0;
                $pago->numero_recibo = 'REC-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($pago) {
            $pago->factura->registrarPago($pago->monto);
            
            if ($pago->cobro) {
                $pago->cobro->recalcularTotales();
            }
        });
    }
}
