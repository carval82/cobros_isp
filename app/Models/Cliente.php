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
        'dv',
        'tipo_documento',
        'tipo_persona',
        'regimen',
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

    public static function tiposDocumento(): array
    {
        return [
            'CC' => 'Cédula de ciudadanía',
            'NIT' => 'NIT / RUT',
            'CE' => 'Cédula de extranjería',
            'TI' => 'Tarjeta de identidad',
            'PP' => 'Pasaporte',
        ];
    }

    public function documentoLimpio(): string
    {
        return preg_replace('/\D+/', '', (string) $this->documento) ?: '';
    }

    public function calcularDv(?string $numero = null): ?string
    {
        $nit = $numero ?? $this->documentoLimpio();
        if ($nit === '') {
            return null;
        }

        $primos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $suma = 0;
        $len = strlen($nit);
        for ($i = 0; $i < $len; $i++) {
            $suma += (int) $nit[$len - 1 - $i] * ($primos[$i] ?? 0);
        }
        $mod = $suma % 11;

        return (string) ($mod > 1 ? 11 - $mod : $mod);
    }

    public function documentoConDv(): string
    {
        $doc = $this->documentoLimpio();
        if ($doc === '') {
            return '';
        }
        $dv = $this->dv ?: ($this->tipo_documento === 'NIT' ? $this->calcularDv($doc) : null);

        return $dv !== null && $dv !== '' ? $doc . '-' . $dv : $doc;
    }

    public function faltantesFacturaElectronica(): array
    {
        $faltan = [];
        if (! $this->tipo_documento) {
            $faltan[] = 'tipo de documento';
        }
        if ($this->documentoLimpio() === '') {
            $faltan[] = 'NIT o cédula';
        }
        if ($this->tipo_documento === 'NIT' && ! filled($this->dv) && ! $this->calcularDv()) {
            $faltan[] = 'dígito de verificación (DV)';
        }
        if (! filled($this->email)) {
            $faltan[] = 'correo electrónico';
        }
        if (! filled($this->direccion)) {
            $faltan[] = 'dirección';
        }
        if (! filled($this->municipio)) {
            $faltan[] = 'municipio';
        }

        return $faltan;
    }

    public function listoParaFacturaElectronica(): bool
    {
        return $this->faltantesFacturaElectronica() === [];
    }

    public static function normalizarIdentificacion(array $data): array
    {
        $doc = trim((string) ($data['documento'] ?? ''));
        if (preg_match('/^(\d+)\s*-\s*(\d)$/', $doc, $m)) {
            $data['documento'] = $m[1];
            if (empty($data['dv'])) {
                $data['dv'] = $m[2];
            }
            if (($data['tipo_documento'] ?? 'CC') === 'CC' && strlen($m[1]) >= 9) {
                $data['tipo_documento'] = 'NIT';
            }
        }

        $tipo = $data['tipo_documento'] ?? 'CC';
        if (empty($data['tipo_persona'])) {
            $data['tipo_persona'] = $tipo === 'NIT' ? 'juridica' : 'natural';
        }
        if (empty($data['regimen'])) {
            $data['regimen'] = 'simplificado';
        }
        if ($tipo === 'NIT' && empty($data['dv']) && ! empty($data['documento'])) {
            $data['dv'] = (new static(['documento' => $data['documento']]))->calcularDv();
        }

        return $data;
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
