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
        'token_publico',
        'alegra_id',
        'enviada_whatsapp_at',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'recargo' => 'decimal:2',
        'total' => 'decimal:2',
        'saldo' => 'decimal:2',
        'enviada_whatsapp_at' => 'datetime',
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

    public function asegurarTokenPublico(): string
    {
        if (! $this->token_publico) {
            $this->token_publico = \Illuminate\Support\Str::random(40);
            $this->save();
        }

        return $this->token_publico;
    }

    public function urlPublica(): string
    {
        return url('/f/' . $this->asegurarTokenPublico());
    }

    public function urlWhatsApp(): ?string
    {
        $this->loadMissing('cliente');
        $tel = preg_replace('/\D+/', '', $this->cliente->celular ?: $this->cliente->telefono ?: '');
        if (! $tel) {
            return null;
        }
        if (strlen($tel) === 10 && str_starts_with($tel, '3')) {
            $tel = '57' . $tel;
        }
        $texto = "Hola {$this->cliente->nombre}, te compartimos tu factura {$this->numero} de INTERVEREDANET ({$this->periodo}) por $"
            . number_format((float) $this->saldo, 0, ',', '.')
            . ". Puedes verla aquí, sin necesidad de iniciar sesión: "
            . $this->urlPublica();

        return 'https://wa.me/' . $tel . '?text=' . rawurlencode($texto);
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
            if (empty($factura->token_publico)) {
                $factura->token_publico = \Illuminate\Support\Str::random(40);
            }
        });
    }
}
