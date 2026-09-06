<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use SoftDeletes, HasApiTokens;

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
        'tipo_alta',
        'factura_electronica',
        'alegra_contact_id',
        'primer_mes_facturable',
        'primer_anio_facturable',
        'fecha_instalacion',
        'notas',
        'cobrador_id',
        'pin',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'primer_mes_facturable' => 'integer',
        'primer_anio_facturable' => 'integer',
        'factura_electronica' => 'boolean',
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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function servicioActivo()
    {
        return $this->servicios()->where('estado', 'activo')->first();
    }

    public function saldoPendiente(): float
    {
        return $this->facturas()->whereIn('estado', ['pendiente', 'parcial', 'vencida'])->sum('saldo');
    }

    public function esNuevo(): bool
    {
        return $this->tipo_alta === 'nuevo';
    }

    public function puedeFacturarseEn(int $mes, int $anio): bool
    {
        if (! $this->primer_anio_facturable || ! $this->primer_mes_facturable) {
            return true;
        }

        if ($anio > $this->primer_anio_facturable) {
            return true;
        }

        if ($anio < $this->primer_anio_facturable) {
            return false;
        }

        return $mes >= $this->primer_mes_facturable;
    }

    public function etiquetaPrimeraFactura(): ?string
    {
        if (! $this->primer_mes_facturable || ! $this->primer_anio_facturable) {
            return null;
        }

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return ($meses[$this->primer_mes_facturable] ?? $this->primer_mes_facturable)
            . ' ' . $this->primer_anio_facturable;
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
