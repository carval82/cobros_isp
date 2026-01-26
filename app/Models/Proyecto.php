<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'ubicacion',
        'municipio',
        'color',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function planes()
    {
        return $this->hasMany(PlanServicio::class);
    }

    public function cobradores()
    {
        return $this->hasMany(Cobrador::class);
    }

    public function cobradoresAsignados()
    {
        return $this->belongsToMany(Cobrador::class, 'cobrador_proyecto')
            ->withPivot('comision_porcentaje')
            ->withTimestamps();
    }

    public function getTotalClientesAttribute()
    {
        return $this->clientes()->count();
    }

    public function getClientesActivosAttribute()
    {
        return $this->clientes()->where('estado', 'activo')->count();
    }

    public function getTotalRecaudadoMesAttribute()
    {
        $mes = now()->month;
        $anio = now()->year;
        
        return $this->clientes()
            ->join('servicios', 'clientes.id', '=', 'servicios.cliente_id')
            ->join('facturas', 'servicios.id', '=', 'facturas.servicio_id')
            ->join('pagos', 'facturas.id', '=', 'pagos.factura_id')
            ->whereMonth('pagos.fecha_pago', $mes)
            ->whereYear('pagos.fecha_pago', $anio)
            ->sum('pagos.monto');
    }
}
