<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipacionProyecto extends Model
{
    protected $table = 'participaciones_proyecto';

    protected $fillable = [
        'proyecto_id',
        'socio_nombre',
        'socio_documento',
        'socio_telefono',
        'porcentaje',
        'activo',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
