<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'gastos_proyecto';

    protected $fillable = [
        'proyecto_id',
        'categoria',
        'descripcion',
        'monto',
        'fecha',
        'proveedor',
        'factura_numero',
        'notas',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public static function categorias(): array
    {
        return [
            'internet' => 'Pago de Internet',
            'equipos' => 'Compra de Equipos',
            'mantenimiento' => 'Mantenimiento',
            'transporte' => 'Transporte',
            'otros' => 'Otros Gastos',
        ];
    }
}
